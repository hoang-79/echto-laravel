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
    | Das Paket zählt Links und erkennt Wegwerf-Mailadressen und meldet Echto nur das Ergebnis — eine Zahl und einen
    | Wahrheitswert. Der Inhalt des Formulars verlässt diesen Server nicht.
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

];
