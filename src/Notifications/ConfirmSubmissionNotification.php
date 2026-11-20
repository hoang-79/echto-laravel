<?php

namespace Echto\Laravel\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mail an den Absender einer zurückgehaltenen Einsendung (F-08, Mockup Bereich 19). Sie kommt von der Website der
 * Kundin, mit deren Mailvorlage, und siezt — Echto tritt darin nicht auf. Die Texte lassen sich mit den
 * Übersetzungen der Anwendung ändern.
 */
class ConfirmSubmissionNotification extends Notification
{
    public function __construct(public readonly string $url, public readonly int $hours) {}

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
            ->subject(__('Bitte bestätigen Sie Ihre Nachricht'))
            ->greeting(__('Bitte bestätigen Sie Ihre Nachricht'))
            ->line(__('Sie haben uns eben über ein Formular auf unserer Website geschrieben. Bitte bestätigen Sie mit einem Klick, dass die Nachricht von Ihnen stammt:'))
            ->action(__('Nachricht bestätigen'), $this->url)
            ->line(trans_choice('Der Link gilt eine Stunde.|Der Link gilt :count Stunden.', $this->hours, ['count' => $this->hours]))
            ->line(__('Haben Sie uns nicht geschrieben? Dann müssen Sie nichts tun; die Nachricht wird nicht zugestellt.'))
            ->salutation(' ');
    }
}
