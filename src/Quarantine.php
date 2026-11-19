<?php

namespace Echto\Laravel;

use Echto\Laravel\Models\QuarantinedSubmission;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\Response;

/**
 * Die Quarantäne: Einsendungen, bei denen Echto zweifelt, werden zurückgehalten statt verworfen. Freigeben führt
 * aus, was bei einer angenommenen Einsendung sofort geschehen wäre — dieselbe Route, derselbe Controller, dieselben
 * Felder.
 *
 * Nicht mitgespeichert werden hochgeladene Dateien.
 */
class Quarantine
{
    public function __construct(
        private readonly Application $app,
        private readonly Router $router,
        private readonly Echto $echto,
    ) {}

    public function hold(Request $request, string $form, Verification $verification): QuarantinedSubmission
    {
        $userId = $request->user()?->getAuthIdentifier();

        return QuarantinedSubmission::query()->create([
            'form' => $form,
            'method' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
            'payload' => $this->echto->fieldsOf($request),
            'reasons' => $verification->reasons,
            'user_id' => $userId === null ? null : (string) $userId,
            'unreachable' => ! $verification->reachable,
            'expires_at' => now()->addDays((int) config('echto.quarantine.days', 30)),
        ]);
    }

    /**
     * Gibt die Einsendung frei: Sie läuft weiter, als wäre sie eben abgesendet worden. Scheitert das — etwa an der
     * Prüfung der Felder —, bleibt sie in der Quarantäne, und der Fehler steigt auf.
     */
    public function release(QuarantinedSubmission $submission): Response
    {
        $original = $this->app->make('request');

        // Unter derselben Adresse wie die Betreiberin, damit Weiterleitungen des Controllers auf diese Anwendung zeigen.
        $request = Request::create($original->getSchemeAndHttpHost().$submission->path, $submission->method, $submission->payload);
        $request->attributes->set('echto.released', true);
        $request->setLaravelSession($this->app->make('session.store'));

        if ($submission->user_id !== null) {
            $this->app->make(Auth::class)->guard()->onceUsingId($submission->user_id);
        }

        $request->setUserResolver(fn () => $this->app->make(Auth::class)->guard()->user());

        $this->app->instance('request', $request);

        try {
            // Die Route und ihr Controller, ohne die Middleware davor: Formularschutz und Echto haben beim Absenden
            // schon geprüft.
            $route = $this->router->getRoutes()->match($request);
            $request->setRouteResolver(fn () => $route);
            $this->router->substituteBindings($route);
            $this->router->substituteImplicitBindings($route);

            $response = Router::toResponse($request, $route->run());
        } finally {
            $this->app->instance('request', $original);
        }

        $submission->delete();
        $this->app->make(Client::class)->reportOutcome($submission->form, released: true);

        return $response;
    }

    public function discard(QuarantinedSubmission $submission): void
    {
        $submission->delete();
        $this->app->make(Client::class)->reportOutcome($submission->form, released: false);
    }
}
