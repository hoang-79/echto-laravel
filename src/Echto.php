<?php

namespace Echto\Laravel;

use Closure;
use Echto\Laravel\Events\SubmissionJudged;
use Echto\Laravel\Testing\FakeClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Der Einstieg des Pakets: prüft eine Einsendung, kennt die Bestätigung je Formular und lässt sich für Tests durch
 * eine Attrappe ersetzen.
 */
class Echto
{
    /** @var array<string, Closure(Request): mixed> */
    private array $confirmations = [];

    public function __construct(private readonly Application $app) {}

    /**
     * Fragt Echto nach dem Urteil über die Einsendung. Je Anfrage und Formular genau einmal — ein Token gilt nur
     * für eine Prüfung.
     */
    public function check(Request $request, string $form, string $action = 'submit'): Verification
    {
        $known = $request->attributes->get('echto.verification.'.$form);

        if ($known instanceof Verification) {
            return $known;
        }

        $token = $request->input((string) config('echto.token_field'));
        $input = $this->fieldsOf($request);

        $verification = $this->app->make(Client::class)->verify(
            $form,
            $action,
            is_string($token) ? $token : null,
            $request->ip(),
            [
                ...Features::of($input, array_values(array_filter((array) config('echto.disposable_domains'), is_string(...)))),
                'honeypot_filled' => $request->filled((string) config('echto.honeypot_field')),
            ],
        );

        $request->attributes->set('echto.verification.'.$form, $verification);
        event(new SubmissionJudged($form, $verification));

        return $verification;
    }

    /**
     * Die Felder des Formulars ohne das, was Echto und Laravel selbst hineinlegen.
     *
     * @return array<string, mixed>
     */
    public function fieldsOf(Request $request): array
    {
        /** @var array<string, mixed> $fields */
        $fields = $request->except([(string) config('echto.token_field'), (string) config('echto.honeypot_field'), '_token', '_method']);

        return $fields;
    }

    /**
     * Legt fest, was die Besucherin nach dem Absenden eines Formulars sieht, wenn Echto zurückhält oder abweist —
     * dieselbe Bestätigung wie bei einer angenommenen Einsendung.
     *
     * @param  Closure(Request): mixed  $response
     */
    public function confirmUsing(string $form, Closure $response): void
    {
        $this->confirmations[$form] = $response;
    }

    /**
     * Die Bestätigung für die Besucherin. Sie ist bei allen drei Urteilen dieselbe: Ein Automat lernt nichts, ein
     * Mensch wird nicht erschreckt.
     */
    public function confirmation(Request $request, string $form, ?string $route = null): Response
    {
        if (isset($this->confirmations[$form])) {
            return Route::toResponse($request, ($this->confirmations[$form])($request));
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        if ($route !== null) {
            $target = Route::getRoutes()->getByName($route);
            $current = $request->route();
            $parameters = $target === null || $current === null ? [] : array_intersect_key($current->parameters(), array_flip($target->parameterNames()));

            return redirect()->route($route, $parameters);
        }

        return redirect()->back();
    }

    /**
     * Ersetzt Echto in Tests durch eine Attrappe mit festem Urteil. Es geht keine Anfrage hinaus.
     */
    public function fake(Verdict|string $verdict = Verdict::Pass): FakeClient
    {
        $fake = new FakeClient($verdict instanceof Verdict ? $verdict : Verdict::from($verdict));

        $this->app->instance(Client::class, $fake);

        return $fake;
    }
}
