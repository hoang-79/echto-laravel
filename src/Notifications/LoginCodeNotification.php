<?php

namespace Echto\Laravel\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Code für eine Anmeldung, bei der Echto zweifelt (F-09). Von der Anwendung der Kundin, mit deren Mailvorlage. Der
 * Code steht nicht im Betreff — Betreffzeilen erscheinen auf gesperrten Bildschirmen.
 */
class LoginCodeNotification extends Notification
{
    public function __construct(public readonly string $code, public readonly int $minutes) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Ihr Code für die Anmeldung'))
            ->greeting(__('Ihr Code für die Anmeldung'))
            ->line(__('Bitte geben Sie diesen Code im Fenster ein, in dem Sie sich gerade anmelden:'))
            ->line('**'.substr($this->code, 0, 3).' '.substr($this->code, 3).'**')
            ->line(trans_choice('Der Code gilt eine Minute.|Der Code gilt :count Minuten.', $this->minutes, ['count' => $this->minutes]))
            ->line(__('Haben Sie sich nicht angemeldet? Dann müssen Sie nichts tun: Ohne den Code geht die Anmeldung nicht weiter.'))
            ->salutation(' ');
    }
}
