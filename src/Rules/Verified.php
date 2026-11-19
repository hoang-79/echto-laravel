<?php

namespace Echto\Laravel\Rules;

use Closure;
use Echto\Laravel\Echto;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validierungsregel für Anwendungen, die ohne die Middleware arbeiten: `'echto-token' => [new Verified('kontakt')]`.
 * Sie scheitert nur, wenn Echto abweist. Bei Zweifel besteht sie; ob zurückgehalten werden soll, sagt danach
 * `Echto::check($request, 'kontakt')->held()` — die Regel kann eine Einsendung nicht selbst in die Quarantäne legen.
 *
 * Wer die Quarantäne will, nimmt die Middleware `echto:…`.
 */
class Verified implements ValidationRule
{
    public function __construct(private readonly string $form, private readonly string $action = 'submit') {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (app(Echto::class)->check(request(), $this->form, $this->action)->blocked()) {
            $fail(__('Die Einsendung konnte nicht angenommen werden.'));
        }
    }
}
