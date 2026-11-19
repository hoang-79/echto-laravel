<?php

namespace Echto\Laravel\View\Components;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * `<x-echto-widget form="kontakt" />` im Formular. Kennung der Website und Adresse von Echto kommen aus der
 * Konfiguration; das Skript wird erst geladen, wenn jemand ein Formular benutzt.
 */
class Widget extends Component
{
    public string $site;

    public string $endpoint;

    public function __construct(public string $form, public ?string $action = null, public ?string $theme = null)
    {
        $this->site = (string) config('echto.site');
        $this->endpoint = rtrim((string) config('echto.url'), '/');
    }

    public function render(): View
    {
        return app(ViewFactory::class)->make('echto::widget');
    }
}
