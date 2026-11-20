<?php

namespace Echto\Laravel\Http\Controllers;

use Echto\Laravel\Models\QuarantinedSubmission;
use Echto\Laravel\Quarantine;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

/**
 * Die Seite hinter dem Link in der Mail (F-08). Der Link öffnet nur eine Seite mit einem Knopf; bestätigt wird erst
 * mit dem Knopf. So bestätigt kein Programm, das Links in Mails vorab öffnet, an Stelle des Absenders.
 */
class ConfirmationController extends Controller
{
    public function __construct(private readonly ViewFactory $views) {}

    public function show(Request $request, int $submission): View|Response
    {
        $held = $this->awaiting($request, $submission);

        if ($held === null) {
            return $this->expired($request);
        }

        return $this->views->make('echto::visitor.confirm', ['action' => $request->fullUrl()]);
    }

    public function confirm(Request $request, int $submission, Quarantine $quarantine): View|Response
    {
        $held = $this->awaiting($request, $submission);

        if ($held === null) {
            return $this->expired($request);
        }

        $returnUrl = $held->return_url;

        try {
            $quarantine->release($held, byVisitor: true);
        } catch (ValidationException) {
            // Die Felder gelten heute nicht mehr (etwa eine geänderte Regel der Anwendung): Die Einsendung bleibt in
            // der Liste, die Betreiberin entscheidet.
            $held->update(['confirm_until' => null]);

            return $this->views->make('echto::visitor.confirmed', ['returnUrl' => $returnUrl]);
        }

        return $this->views->make('echto::visitor.confirmed', ['returnUrl' => $returnUrl]);
    }

    private function awaiting(Request $request, int $id): ?QuarantinedSubmission
    {
        if (! $request->hasValidSignature()) {
            return null;
        }

        $held = QuarantinedSubmission::query()->find($id);

        return $held !== null && $held->awaitsConfirmation() ? $held : null;
    }

    /**
     * Abgelaufen oder schon benutzt: Die Seite sagt, was geschehen ist und was zu tun bleibt.
     */
    private function expired(Request $request): Response
    {
        return response($this->views->make('echto::visitor.expired', [
            'used' => $request->hasValidSignature(),
            'hours' => (int) config('echto.confirmation.hours', 24),
        ]), 410);
    }
}
