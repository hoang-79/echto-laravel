# Echto für Laravel

Eine Zeile an der Route schützt ein Formular; was [Echto](https://echto.ch) bezweifelt, landet in der Quarantäne statt im Papierkorb. Für Laravel 12 und 13, ab PHP 8.3.

```bash
composer require nguyen-development/echto-laravel
php artisan migrate
```

`.env`:

```dotenv
ECHTO_URL=https://app.echto.ch
ECHTO_SITE=site_…
ECHTO_SECRET=echto_sk_…
```

Im Formular:

```blade
<form method="POST" action="/kontakt">
    @csrf
    …
    <x-echto-widget form="kontakt" />
    <button>Senden</button>
</form>
```

An der Route:

```php
Route::post('/kontakt', KontaktController::class)
    ->middleware('echto:kontakt,kontakt.danke');
```

`kontakt` ist die Kennung des Formulars, `kontakt.danke` die Route der Bestätigung — die Besucherin sieht sie in jedem Fall. Zurückgehaltenes steht unter `/echto/quarantine` und lässt sich dort freigeben. Antwortet Echto nicht innert zwei Sekunden, hält das Paket zurück, statt abzuweisen.

Weiter im Paket: Regeln für den Inhalt, die auf deinem Server geprüft werden (der Text verlässt ihn nicht), Bestätigung per E-Mail im Zweifel (`config/echto.php` → `confirmation`), Code bei der Anmeldung im Zweifel (`login`), die Regel `Verified` für Anwendungen ohne Middleware und `Echto::fake()` für Tests.
