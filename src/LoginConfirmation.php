<?php

namespace Echto\Laravel;

use Echto\Laravel\Notifications\LoginCodeNotification;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Anmeldung im Zweifel (F-09): Eine Anmeldung lässt sich nicht zurückhalten, und sperren soll Echto niemanden.
 * Zweifelt Echto, schickt das Paket einen Code an die Adresse, mit der sich jemand anmelden will. Wer ihn eingibt,
 * darf sich in den nächsten Minuten noch einmal anmelden — dann ohne Zweifel. Das Passwort wird dabei nirgends
 * abgelegt; es geht mit der zweiten Anmeldung den gewohnten Weg.
 */
class LoginConfirmation
{
    public function __construct(private readonly Cache $cache) {}

    public function covers(string $form): bool
    {
        $forms = (array) config('echto.login.forms', []);

        return in_array('*', $forms, true) || in_array($form, $forms, true);
    }

    /**
     * Hat diese Sitzung den Code für das Formular eben richtig eingegeben? Dann gilt der Zweifel einmal nicht.
     */
    public function consume(Request $request, string $form): bool
    {
        $until = $request->session()->pull('echto.login.'.$form);

        return is_int($until) && $until > now()->getTimestamp();
    }

    /**
     * Schickt den Code und gibt die Kennung des Vorgangs zurück — oder `null`, wenn keine gültige Adresse im
     * Formular steht oder die Adresse in dieser Stunde schon genug Codes bekommen hat.
     */
    public function start(Request $request, string $form): ?string
    {
        $email = $request->input((string) config('echto.login.email_field', 'email'));

        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        $key = 'echto-login-code:'.hash('sha256', mb_strtolower($email));

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return null;
        }

        RateLimiter::hit($key, 3600);

        $id = Str::random(32);
        $code = (string) random_int(100000, 999999);
        $minutes = (int) config('echto.login.minutes', 10);
        $referer = $request->headers->get('referer');

        $this->cache->put('echto:login:'.$id, [
            'form' => $form,
            'hash' => hash_hmac('sha256', $code, (string) config('app.key')),
            'attempts' => 0,
            'return_url' => is_string($referer) && str_starts_with($referer, $request->getSchemeAndHttpHost().'/') ? $referer : url('/'),
        ], now()->addMinutes($minutes));

        Notification::route('mail', $email)->notify(new LoginCodeNotification($code, $minutes));

        return $id;
    }

    /**
     * Prüft den Code. Nach zu vielen Fehlversuchen gilt der Vorgang nicht mehr.
     *
     * @return array{ok: bool, return_url: string|null, form: string|null}
     */
    public function verify(string $id, string $code): array
    {
        /** @var array{form: string, hash: string, attempts: int, return_url: string}|null $pending */
        $pending = $this->cache->get('echto:login:'.$id);

        if (! is_array($pending) || $pending['attempts'] >= (int) config('echto.login.attempts', 5)) {
            return ['ok' => false, 'return_url' => null, 'form' => null];
        }

        if (! hash_equals($pending['hash'], hash_hmac('sha256', preg_replace('/\D/', '', $code) ?? '', (string) config('app.key')))) {
            $pending['attempts']++;
            $this->cache->put('echto:login:'.$id, $pending, now()->addMinutes((int) config('echto.login.minutes', 10)));

            return ['ok' => false, 'return_url' => $pending['return_url'], 'form' => $pending['form']];
        }

        $this->cache->forget('echto:login:'.$id);

        return ['ok' => true, 'return_url' => $pending['return_url'], 'form' => $pending['form']];
    }

    public function pending(string $id): bool
    {
        return is_array($this->cache->get('echto:login:'.$id));
    }
}
