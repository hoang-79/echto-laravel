<?php

namespace Echto\Laravel\Http\Controllers;

use Echto\Laravel\LoginConfirmation;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Code eingeben, wenn Echto bei einer Anmeldung zweifelt (F-09). Nach dem richtigen Code darf sich diese Sitzung
 * innert der Gültigkeit einmal ohne Zweifel anmelden.
 */
class LoginCodeController extends Controller
{
    public function __construct(private readonly ViewFactory $views, private readonly LoginConfirmation $logins) {}

    public function show(string $id): View|Response
    {
        if (! $this->logins->pending($id)) {
            return response($this->views->make('echto::visitor.code-expired'), 410);
        }

        return $this->views->make('echto::visitor.code', ['action' => route('echto.login.verify', $id), 'error' => session('echto.code_error')]);
    }

    public function verify(Request $request, string $id): RedirectResponse|Response
    {
        $result = $this->logins->verify($id, (string) $request->input('code', ''));

        if ($result['form'] === null) {
            return response($this->views->make('echto::visitor.code-expired'), 410);
        }

        if (! $result['ok']) {
            return redirect()->route('echto.login.show', $id)->with('echto.code_error', __('Dieser Code stimmt nicht. Bitte prüfen Sie die Ziffern.'));
        }

        $request->session()->put('echto.login.'.$result['form'], now()->addMinutes((int) config('echto.login.minutes', 10))->getTimestamp());

        return redirect()->to((string) $result['return_url'])->with('echto.login_confirmed', true);
    }
}
