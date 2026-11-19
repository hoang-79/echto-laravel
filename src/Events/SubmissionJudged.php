<?php

namespace Echto\Laravel\Events;

use Echto\Laravel\Verification;

/**
 * Echto hat über eine Einsendung geurteilt. Für eigene Auswertungen der Anwendung; der Inhalt der Einsendung ist
 * nicht Teil des Ereignisses.
 */
final readonly class SubmissionJudged
{
    public function __construct(
        public string $form,
        public Verification $verification,
    ) {}
}
