<?php

namespace Echto\Laravel\Http\Middleware;

use Closure;
use Echto\Laravel\Confirmation;
use Echto\Laravel\Echto;
use Echto\Laravel\LoginConfirmation;
use Echto\Laravel\Quarantine;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Schützt eine Route: `->middleware('echto:kontakt')`. Echto urteilt, bevor der Controller läuft.
 *
 *  - angenommen: der Controller läuft wie immer
 *  - Zweifel: die Einsendung kommt in die Quarantäne; der Controller läuft erst beim Freigeben — oder, wenn das
 *    Formular dafür eingerichtet ist, sobald der Absender per Link bestätigt (F-08). Bei einer Anmeldung kommt
 *    statt der Quarantäne ein Code per Mail (F-09)
 *  - abgewiesen: der Controller läuft nicht
 *
 * In allen drei Fällen sieht die Besucherin dieselbe Bestätigung. Welche, sagt der zweite Wert — der Name einer
 * Route: `->middleware('echto:kontakt,kontakt.danke')` — oder `Echto::confirmUsing()`.
 */
class Protect
{
    public function __construct(
        private readonly Echto $echto,
        private readonly Quarantine $quarantine,
        private readonly Confirmation $confirmations,
        private readonly LoginConfirmation $logins,
    ) {}

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

        // Anmeldung: Zweifel heisst Code per Mail, nicht Quarantäne (F-09). Wer den Code eben eingegeben hat, kommt
        // einmal ohne Zweifel durch; abgewiesen bleibt abgewiesen.
        if ($verification->held() && $this->logins->covers($form) && $request->hasSession()) {
            if ($this->logins->consume($request, $form)) {
                return $next($request);
            }

            $id = $this->logins->start($request, $form);

            return $id === null ? $this->echto->confirmation($request, $form, $confirmation) : redirect()->route('echto.login.show', $id);
        }

        if ($verification->held()) {
            $held = $this->quarantine->hold($request, $form, $verification);
            $email = $this->confirmations->addressFor($request, $form, $verification);

            // Bestätigung per E-Mail (F-08): Statt zu warten, bestätigt der Absender selbst.
            if ($email !== null) {
                $this->confirmations->send($request, $held, $email);
                if ($request->hasSession()) {
                    $request->session()->flash('echto.confirmation', $email);
                }
            }
        }

        return $this->echto->confirmation($request, $form, $confirmation);
    }
}
