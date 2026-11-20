<?php

namespace Echto\Laravel;

use Echto\Laravel\Models\QuarantinedSubmission;
use Echto\Laravel\Notifications\ConfirmSubmissionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

/**
 * Bestätigung per E-Mail (F-08, Mockup Bereich 19): Hält Echto eine Einsendung zurück, bekommt der Absender einen
 * Link. Nach dem Klick gilt die Einsendung — wie beim Freigeben durch die Betreiberin. Die Mail geht von der
 * Anwendung der Kundin aus; Echto erfährt die Adresse nie.
 */
class Confirmation
{
    /**
     * Die Adresse, an die eine Bestätigung gehen darf — oder `null`: Formular nicht dafür eingerichtet, keine
     * gültige Adresse im Formular, eine Wegwerf-Adresse (deren Postfach kann jeder lesen, auch ein Automat), oder
     * die Adresse hat in dieser Stunde schon genug Bestätigungen bekommen.
     */
    public function addressFor(Request $request, string $form, Verification $verification): ?string
    {
        if (in_array('content.disposable_mail', array_column($verification->reasons, 'code'), true)) {
            return null;
        }

        $forms = (array) config('echto.confirmation.forms', []);

        if (! in_array('*', $forms, true) && ! in_array($form, $forms, true)) {
            return null;
        }

        $email = $request->input((string) config('echto.confirmation.email_field', 'email'));

        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        $key = 'echto-confirm:'.hash('sha256', mb_strtolower($email));

        if (RateLimiter::tooManyAttempts($key, (int) config('echto.confirmation.per_hour', 3))) {
            return null;
        }

        RateLimiter::hit($key, 3600);

        return $email;
    }

    /**
     * Schickt den Link und merkt sich, bis wann er gilt und wohin der Absender danach zurückkann.
     */
    public function send(Request $request, QuarantinedSubmission $submission, string $email): void
    {
        $until = now()->addHours((int) config('echto.confirmation.hours', 24));
        $referer = $request->headers->get('referer');

        $submission->update([
            'confirm_until' => $until,
            // Nur eine Adresse dieser Anwendung — sonst würde der Link nach dem Bestätigen auf eine fremde Seite führen.
            'return_url' => is_string($referer) && str_starts_with($referer, $request->getSchemeAndHttpHost().'/') ? $referer : null,
        ]);

        Notification::route('mail', $email)->notify(new ConfirmSubmissionNotification(
            URL::temporarySignedRoute('echto.confirmation.show', $until, ['submission' => $submission->id]),
            (int) config('echto.confirmation.hours', 24),
        ));
    }
}
