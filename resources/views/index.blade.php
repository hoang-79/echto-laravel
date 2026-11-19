@extends('echto::layout')
@section('title', __('Zurückgehaltene Einsendungen'))
@section('content')
    @if ($submissions->total() === 0)
        <div class="empty">
            <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="9" fill="currentColor"/><path d="M5.8 10.4l2.8 2.8 5.6-6" fill="none" stroke="var(--card)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <h1>{{ __('Nichts zurückgehalten') }}</h1>
            <p class="muted">{{ __('Alle Einsendungen sind angekommen. Hält Echto wieder eine zurück, steht sie hier.') }}</p>
        </div>
    @else
        @if ($outage && $outage->held > 0)
            <div class="notice warn" role="status">
                <div>
                    <strong>{{ __('Echto war zeitweise nicht erreichbar.') }}</strong>
                    {{ trans_choice('{1} Eine Einsendung hat das Paket in dieser Zeit angenommen und zurückgehalten|[2,*] :count Einsendungen hat das Paket in dieser Zeit angenommen und zurückgehalten', $outage->held, ['count' => $outage->held]) }}
                    ({{ \Illuminate\Support\Carbon::parse($outage->first_at)->format('d.m.Y H:i') }}@if ($outage->first_at !== $outage->last_at) – {{ \Illuminate\Support\Carbon::parse($outage->last_at)->format('d.m.Y H:i') }}@endif).
                    {{ __('Abgewiesen wurde niemand. Sieh sie durch und gib frei, was echt ist.') }}
                </div>
            </div>
        @endif

        <h1 @if ($outage && $outage->held > 0) style="margin-top: 24px" @endif>
            {{ trans_choice('{1} 1 Einsendung wartet auf dich|[2,*] :count Einsendungen warten auf dich', $submissions->total(), ['count' => $submissions->total()]) }}
        </h1>
        <p class="muted" style="margin-top: 4px">{{ __('Echto war sich bei diesen Einsendungen nicht sicher. Gib frei, was echt ist – es läuft dann weiter, als wäre es eben abgesendet worden.') }}</p>

        <ul class="list">
            @foreach ($submissions as $submission)
                <li>
                    <div>
                        <p><strong>{{ $submission->sender() ?? __('Einsendung :id', ['id' => $submission->id]) }}</strong></p>
                        <p class="muted small">{{ __('Formular :form', ['form' => $submission->form]) }} · {{ $submission->created_at?->format('d.m.Y H:i') }}</p>
                        @if ($submission->excerpt() !== '')
                            <p style="margin-top: 6px">{{ $submission->excerpt() }}</p>
                        @endif
                        @foreach ($submission->reasons as $reason)
                            <p class="reason">{{ __('Zurückgehalten:') }} {{ $reason['text'] ?? $reason['code'] ?? '' }}</p>
                        @endforeach
                        <p style="margin-top: 6px"><a href="{{ route('echto.quarantine.show', $submission) }}">{{ __('Ganz ansehen') }}</a></p>
                    </div>
                    <div class="actions">
                        <form method="post" action="{{ route('echto.quarantine.release', $submission) }}">@csrf
                            <button type="submit" aria-label="{{ __('Einsendung :id freigeben', ['id' => $submission->id]) }}">{{ __('Freigeben') }}</button>
                        </form>
                        <form method="post" action="{{ route('echto.quarantine.discard', $submission) }}">@csrf @method('DELETE')
                            <button type="submit" class="secondary" aria-label="{{ __('Einsendung :id verwerfen', ['id' => $submission->id]) }}">{{ __('Verwerfen') }}</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($submissions->hasPages())
            <nav aria-label="{{ __('Seiten') }}" class="small">
                @if ($submissions->previousPageUrl()) <a href="{{ $submissions->previousPageUrl() }}">{{ __('‹ Neuere') }}</a> @endif
                @if ($submissions->nextPageUrl()) <a href="{{ $submissions->nextPageUrl() }}">{{ __('Ältere ›') }}</a> @endif
            </nav>
        @endif

        <p class="muted small" style="margin-top: 16px">{{ trans_choice('{1} Was du weder freigibst noch verwirfst, wird nach einem Tag gelöscht.|[2,*] Was du weder freigibst noch verwirfst, wird nach :count Tagen gelöscht.', $days, ['count' => $days]) }} {{ __('Die Einsendungen liegen nur auf diesem Server, nicht bei Echto.') }}</p>
    @endif
@endsection
