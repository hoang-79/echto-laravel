<?php

namespace Echto\Laravel;

use Echto\Laravel\Http\Middleware\Protect;
use Echto\Laravel\View\Components\Widget;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class EchtoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/echto.php', 'echto');

        $this->app->singleton(Echto::class);
        $this->app->singleton(Quarantine::class);
        $this->app->singleton(Confirmation::class);
        $this->app->bind(LoginConfirmation::class, fn ($app): LoginConfirmation => new LoginConfirmation($app->make('cache.store')));
        $this->app->bind(SelfCheck::class, fn ($app): SelfCheck => new SelfCheck($app->make('cache.store')));
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('echto', Protect::class);

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'echto');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        Blade::component('echto-widget', Widget::class);
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'echto');

        // Wer die Quarantäne sehen darf, legt die Anwendung fest; ohne eigene Regel nur die lokale Umgebung.
        if (! Gate::has('viewEchtoQuarantine')) {
            Gate::define('viewEchtoQuarantine', fn ($user = null): bool => $this->app->environment('local'));
        }

        if (config('echto.quarantine.enabled', true)) {
            Route::middleware((array) config('echto.quarantine.middleware', ['web']))
                ->prefix((string) config('echto.quarantine.path', 'echto/quarantine'))
                ->name('echto.quarantine.')
                ->group(__DIR__.'/../routes/web.php');
        }

        // Bestätigung per E-Mail und Code bei der Anmeldung — Seiten für Besucher, mit Sitzung und Formularschutz.
        Route::middleware((array) config('echto.confirmation.middleware', ['web']))->group(__DIR__.'/../routes/visitor.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/echto.php' => config_path('echto.php')], 'echto-config');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/echto')], 'echto-views');
        }
    }
}
