<?php

namespace Echto\Laravel\Http\Controllers;

use Echto\Laravel\Models\QuarantinedSubmission;
use Echto\Laravel\Quarantine;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Oberfläche der Quarantäne: Liste, Einzelansicht, Freigeben, Verwerfen. Wer sie sehen darf, bestimmt das Gate
 * `viewEchtoQuarantine`.
 */
class QuarantineController extends Controller
{
    public function __construct(private readonly ViewFactory $views) {}

    public function index(): View
    {
        Gate::authorize('viewEchtoQuarantine');

        $held = QuarantinedSubmission::query()->where('expires_at', '>', now())->latest('id');

        return $this->views->make('echto::index', [
            'submissions' => $held->paginate(25),
            'outage' => (clone $held)->where('unreachable', true)->reorder()->selectRaw('min(created_at) as first_at, max(created_at) as last_at, count(*) as held')->toBase()->first(),
            'days' => (int) config('echto.quarantine.days', 30),
        ]);
    }

    public function show(QuarantinedSubmission $submission): View
    {
        Gate::authorize('viewEchtoQuarantine');

        return $this->views->make('echto::show', ['submission' => $submission]);
    }

    public function release(QuarantinedSubmission $submission, Quarantine $quarantine): RedirectResponse
    {
        Gate::authorize('viewEchtoQuarantine');

        try {
            $quarantine->release($submission);
        } catch (ValidationException $exception) {
            return redirect()->route('echto.quarantine.show', $submission)->with('echto.error', __('Die Einsendung lässt sich nicht freigeben: :reason', [
                'reason' => collect($exception->errors())->flatten()->first(),
            ]));
        }

        return redirect()->route('echto.quarantine.index')->with('echto.status', __('Freigegeben. Die Einsendung ist weitergelaufen, als wäre sie eben abgesendet worden.'));
    }

    public function discard(QuarantinedSubmission $submission, Quarantine $quarantine): RedirectResponse
    {
        Gate::authorize('viewEchtoQuarantine');

        $quarantine->discard($submission);

        return redirect()->route('echto.quarantine.index')->with('echto.status', __('Verworfen. Die Einsendung ist gelöscht.'));
    }
}
