<?php

namespace Echto\Laravel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Spricht mit Echto. Antwortet der Dienst nicht rechtzeitig, entscheidet das Paket selbst: Es lässt durch und hält
 * zurück — keine Einsendung geht verloren, keine geht ungeprüft hinaus. Ein Token mit gefälschter Signatur wird
 * auch dann abgewiesen.
 */
class Client
{
    public function __construct(private readonly Http $http, private readonly SelfCheck $selfCheck) {}

    /**
     * @param  array<string, int|float|bool>  $features  Merkmale des Inhalts — nur Zahlen und Wahrheitswerte (`Features`)
     * @param  string|null  $userAgent  Kennung des Browsers oder Automaten — damit Echto bekannte Automaten erkennt (M-02)
     * @param  string|null  $botKey  Schlüssel eines eigenen Automaten aus der Kopfzeile `Echto-Bot-Key` (M-04)
     */
    public function verify(string $form, string $action, ?string $token, ?string $ip, array $features, ?string $userAgent = null, ?string $botKey = null): Verification
    {
        if (! $this->configured()) {
            Log::warning('Echto ist nicht eingerichtet: ECHTO_SITE und ECHTO_SECRET fehlen. Einsendungen werden zurückgehalten.');

            return $this->withoutEchto($form, $action, $token, 'echto.not_configured', 'Echto ist in dieser Anwendung noch nicht eingerichtet.');
        }

        try {
            $response = $this->request()->post('/v1/verify', array_filter([
                'token' => $token,
                'form' => $form,
                'action' => $action,
                'remote_ip' => $ip,
                'user_agent' => $userAgent === null ? null : mb_substr($userAgent, 0, 512),
                'bot_key' => $botKey,
                'features' => $features,
            ], fn (mixed $value): bool => $value !== null && $value !== ''));
        } catch (ConnectionException) {
            return $this->withoutEchto($form, $action, $token);
        }

        if ($response->failed()) {
            return $this->afterError($response, $form, $action, $token);
        }

        $verdict = Verdict::tryFrom((string) $response->json('verdict'));

        if ($verdict === null) {
            return $this->withoutEchto($form, $action, $token);
        }

        $this->refreshKeys();

        /** @var list<array{code: string, text: string}> $reasons */
        $reasons = array_values(array_filter((array) $response->json('reasons'), is_array(...)));

        return new Verification($verdict, $reasons);
    }

    /**
     * Die Regeln für den Inhalt von Echto (F-10): Listen und Wörter, angewendet in `Features`. Das Paket behält sie
     * so lange, wie Echto sagt (Vorgabe eine Stunde). Antwortet Echto nicht, gelten fünf Minuten lang die Listen aus
     * der Konfiguration des Pakets — eine Einsendung wartet nie auf die Regeln.
     *
     * @return array{disposable_domains?: list<string>, patterns?: array<string, list<string>>, languages?: array<string, list<string>>, words?: array<array-key, string>}
     */
    public function rules(): array
    {
        /** @var array{disposable_domains?: list<string>, patterns?: array<string, list<string>>, languages?: array<string, list<string>>, words?: array<array-key, string>}|null $cached */
        $cached = Cache::get('echto.rules');

        if (is_array($cached)) {
            return $cached;
        }

        $fallback = ['disposable_domains' => array_values(array_filter((array) config('echto.disposable_domains'), is_string(...)))];

        if (! $this->configured()) {
            return $fallback;
        }

        try {
            $response = $this->request()->get('/v1/rules');
        } catch (Throwable) {
            $response = null;
        }

        if ($response === null || ! $response->successful() || ! is_array($response->json())) {
            Cache::put('echto.rules', $fallback, 300);

            return $fallback;
        }

        $rules = [
            'disposable_domains' => array_values(array_filter((array) $response->json('disposable_domains'), is_string(...))),
            'patterns' => array_filter((array) $response->json('patterns'), is_array(...)),
            'languages' => array_filter((array) $response->json('languages'), is_array(...)),
            'words' => array_filter((array) $response->json('words'), is_string(...)),
        ];

        /** @var array{disposable_domains: list<string>, patterns: array<string, list<string>>, languages: array<string, list<string>>, words: array<array-key, string>} $rules */
        Cache::put('echto.rules', $rules, max(60, (int) $response->json('cache_seconds', 3600)));

        return $rules;
    }

    /**
     * Meldet Echto, dass eine zurückgehaltene Einsendung freigegeben, vom Absender bestätigt oder verworfen wurde —
     * nur dieses eine Wort.
     * Gelingt die Meldung nicht, ist das kein Fehler für die Betreiberin.
     */
    public function reportOutcome(string $form, bool $released, bool $byVisitor = false): void
    {
        if (! $this->configured()) {
            return;
        }

        $outcome = match (true) {
            $released && $byVisitor => 'confirmed',
            $released => 'released',
            default => 'discarded',
        };

        try {
            $this->request()->post('/v1/outcome', ['form' => $form, 'outcome' => $outcome]);
        } catch (Throwable) {
            // Die Kennzahl bei Echto ist dann um eins zu klein; die Einsendung ist trotzdem freigegeben.
        }
    }

    private function afterError(Response $response, string $form, string $action, ?string $token): Verification
    {
        // 401, 403, 422: Die Einbindung stimmt nicht (Schlüssel, Angaben). Das soll die Entwicklerin erfahren.
        if ($response->clientError() && $response->status() !== 429) {
            Log::warning('Echto hat die Anfrage abgelehnt: '.$response->json('error.code', (string) $response->status()).' — '.$response->json('error.message', ''));

            return $this->withoutEchto($form, $action, $token, 'echto.rejected', 'Echto hat die Anfrage dieser Anwendung abgelehnt: '.$response->json('error.message', (string) $response->status()));
        }

        return $this->withoutEchto($form, $action, $token);
    }

    private function withoutEchto(string $form, string $action, ?string $token, string $code = 'echto.unreachable', string $text = 'Echto war nicht erreichbar.'): Verification
    {
        $reasons = [['code' => $code, 'text' => $text]];

        if ($token !== null && $token !== '' && $this->selfCheck->accepts($token, (string) config('echto.site'), $form, $action) === false) {
            return new Verification(Verdict::Block, [...$reasons, [
                'code' => 'token.invalid',
                'text' => 'Das Token ist gefälscht, abgelaufen, für etwas anderes ausgestellt oder schon verwendet.',
            ]], reachable: false);
        }

        return new Verification(Verdict::Review, $reasons, reachable: false);
    }

    /**
     * Holt die öffentlichen Schlüssel, solange Echto antwortet — für den Tag, an dem es nicht antwortet.
     */
    private function refreshKeys(): void
    {
        if ($this->selfCheck->hasKeys()) {
            return;
        }

        try {
            /** @var list<array{kid: string, public_key: string}> $keys */
            $keys = array_values(array_filter((array) $this->request()->get('/v1/key')->json('keys'), is_array(...)));

            if ($keys !== []) {
                $this->selfCheck->remember($keys);
            }
        } catch (Throwable) {
            // Beim nächsten Urteil noch einmal.
        }
    }

    private function configured(): bool
    {
        return is_string(config('echto.site')) && config('echto.site') !== '' && is_string(config('echto.secret')) && config('echto.secret') !== '';
    }

    private function request(): PendingRequest
    {
        $timeout = (float) config('echto.timeout', 2);

        return $this->http->baseUrl(rtrim((string) config('echto.url'), '/'))
            ->withToken((string) config('echto.secret'))
            ->acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->connectTimeout($timeout)
            // Lokal stellt die Entwicklungsumgebung das Zertifikat aus; die Prüfung der Kette entfällt nur dort.
            ->withOptions(['verify' => ! app()->environment('local')]);
    }
}
