<?php

namespace Echto\Laravel\Http\Middleware;

use Closure;
use Echto\Laravel\Echto;
use Echto\Laravel\Quarantine;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Schützt eine Route: `->middleware('echto:kontakt')`. Echto urteilt, bevor der Controller läuft.
 *
 *  - angenommen: der Controller läuft wie immer
 *  - Zweifel: die Einsendung kommt in die Quarantäne; der Controller läuft erst beim Freigeben
 *  - abgewiesen: der Controller läuft nicht
 *
 * In allen drei Fällen sieht die Besucherin dieselbe Bestätigung. Welche, sagt der zweite Wert — der Name einer
 * Route: `->middleware('echto:kontakt,kontakt.danke')` — oder `Echto::confirmUsing()`.
 */
class Protect
{
    public function __construct(private readonly Echto $echto, private readonly Quarantine $quarantine) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $form, ?string $confirmation = null): Response
    {
        // Lesende Anfragen sind keine Einsendungen; eine freigegebene Einsendung ist schon geprüft.
        if ($request->isMethodSafe() || $request->attributes->get('echto.released') === true) {
            return $next($request);
        }

        $verification = $this->echto->check($request, $form);

        if ($verification->passed()) {
            return $next($request);
        }

        if ($verification->held()) {
            $this->quarantine->hold($request, $form, $verification);
        }

        return $this->echto->confirmation($request, $form, $confirmation);
    }
}
