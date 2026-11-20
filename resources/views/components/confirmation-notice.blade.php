{{--
    Hinweis nach dem Absenden, wenn der Absender seine Nachricht per E-Mail bestätigen soll (F-08, Mockup Bereich
    19). Gehört auf die Seite, die nach dem Absenden kommt: <x-echto-confirmation-notice />. Gibt nichts aus, wenn
    keine Bestätigung unterwegs ist.
--}}
@if (session()->has('echto.confirmation'))
    <div {{ $attributes->merge(['role' => 'status', 'class' => 'echto-confirmation-notice']) }}>
        <strong>{{ __('Bitte bestätigen Sie Ihre Nachricht per E-Mail.') }}</strong>
        <p>{{ trans_choice('Wir haben einen Link an :email gesendet. Nach dem Klick ist Ihre Nachricht bei uns. Der Link gilt eine Stunde.|Wir haben einen Link an :email gesendet. Nach dem Klick ist Ihre Nachricht bei uns. Der Link gilt :count Stunden.', (int) config('echto.confirmation.hours', 24), ['email' => session('echto.confirmation'), 'count' => (int) config('echto.confirmation.hours', 24)]) }}</p>
    </div>
@endif
