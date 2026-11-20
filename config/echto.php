<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Verbindung zu Echto
    |--------------------------------------------------------------------------
    |
    | Adresse des Dienstes, Kennung der Website (steht im Formular, nicht geheim) und Schlüssel (bleibt auf dem
    | Server). Kennung und Schlüssel zeigt das Konto von Echto bei der Einrichtung.
    |
    */

    'url' => env('ECHTO_URL', 'https://echto.ch'),
    'site' => env('ECHTO_SITE'),
    'secret' => env('ECHTO_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Verhalten bei Ausfall
    |--------------------------------------------------------------------------
    |
    | So viele Sekunden wartet das Paket auf Echto. Kommt keine Antwort, prüft es die Signatur des Tokens selbst und
    | hält die Einsendung zurück: Nichts geht verloren, nichts geht ungeprüft hinaus.
    |
    */

    'timeout' => (float) env('ECHTO_TIMEOUT', 2),

    /*
    |--------------------------------------------------------------------------
    | Felder
    |--------------------------------------------------------------------------
    |
    | Name des Feldes mit dem Token und des Köderfeldes, wie das Widget sie ins Formular legt.
    |
    */

    'token_field' => 'echto-token',
    'honeypot_field' => 'website_url',

    /*
    |--------------------------------------------------------------------------
    | Merkmale des Inhalts
    |--------------------------------------------------------------------------
    |
    | Das Paket holt die Regeln für den Inhalt bei Echto (`GET /v1/rules`: Wegwerf-Dienste, bekannte Muster, Wörter
    | je Sprache, eigene Wort-Regeln), wendet sie hier an und meldet Echto nur das Ergebnis — Zahlen und
    | Wahrheitswerte. Der Inhalt des Formulars verlässt diesen Server nicht. Die Liste unten gilt nur, solange Echto
    | nicht erreichbar ist.
    |
    */

    'disposable_domains' => [
        '10minutemail.com', 'guerrillamail.com', 'mailinator.com', 'sharklasers.com', 'tempmail.com',
        'temp-mail.org', 'throwawaymail.com', 'trashmail.com', 'yopmail.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Quarantäne
    |--------------------------------------------------------------------------
    |
    | Zurückgehaltene Einsendungen liegen verschlüsselt in der Datenbank dieser Anwendung und werden nach `days`
    | Tagen gelöscht. Die Liste steht unter `path`; wer sie sehen darf, bestimmt das Gate `viewEchtoQuarantine`
    | (Vorgabe: nur in der lokalen Umgebung).
    |
    */

    'quarantine' => [
        'enabled' => true,
        'path' => 'echto/quarantine',
        'middleware' => ['web'],
        'days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bestätigung per E-Mail
    |--------------------------------------------------------------------------
    |
    | Statt in der Quarantäne auf die Betreiberin zu warten, bestätigt der Absender im Zweifel selbst: ein Link per
    | Mail, ein Klick, die Einsendung gilt (F-08). Gilt für die Formulare in `forms` (`['*']` für alle); die
    | Mailadresse steht im Feld `email_field`. Die Mail geht von dieser Anwendung aus, mit ihrer Mailvorlage — Echto
    | tritt darin nicht auf. Je Adresse höchstens `per_hour` Mails in der Stunde, damit niemand fremde Postfächer mit
    | Bestätigungen füllt.
    |
    */

    'confirmation' => [
        'forms' => [],
        'email_field' => 'email',
        'hours' => 24,
        'per_hour' => 3,
        'path' => 'echto/bestaetigen',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Anmeldung im Zweifel
    |--------------------------------------------------------------------------
    |
    | Eine Anmeldung lässt sich nicht zurückhalten. Für die Formulare in `forms` schickt das Paket im Zweifel einen
    | Code an die Adresse im Feld `email_field`; nach dem richtigen Code geht die nächste Anmeldung durch (F-09).
    | Das Passwort wird dabei nirgends abgelegt — wer bestätigt hat, meldet sich noch einmal an.
    |
    */

    'login' => [
        'forms' => [],
        'email_field' => 'email',
        'minutes' => 10,
        'attempts' => 5,
        'path' => 'echto/code',
    ],

];
