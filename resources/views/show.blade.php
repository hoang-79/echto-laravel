@extends('echto::layout')
@section('title', __('Einsendung :id', ['id' => $submission->id]))
@section('content')
    <p><a href="{{ route('echto.quarantine.index') }}">{{ __('‹ Alle zurückgehaltenen Einsendungen') }}</a></p>
    <h1 style="margin-top: 12px">{{ $submission->sender() ? __('Einsendung von :sender', ['sender' => $submission->sender()]) : __('Einsendung :id', ['id' => $submission->id]) }}</h1>
    <p class="muted small">{{ __('Formular :form', ['form' => $submission->form]) }} · {{ __('eingegangen :time', ['time' => $submission->created_at?->format('d.m.Y H:i')]) }}</p>

    @foreach ($submission->reasons as $reason)
        <div class="notice warn">
            <div><strong>{{ __('Zurückgehalten:') }} {{ $reason['text'] ?? $reason['code'] ?? '' }}</strong></div>
        </div>
    @endforeach
    @if ($submission->awaitsConfirmation())
        <p class="muted small" style="margin-top: 8px">{{ __('Der Absender hat einen Link zum Bestätigen bekommen (gültig bis :time). Bestätigt er, läuft die Einsendung von selbst weiter.', ['time' => $submission->confirm_until?->format('d.m.Y H:i')]) }}</p>
    @endif
    @if ($submission->unreachable)
        <p class="muted small" style="margin-top: 8px">{{ __('Antwortet Echto nicht, lässt das Paket die Einsendung durch und hält sie zurück. So geht bei einem Ausfall nichts verloren und nichts ungeprüft hinaus.') }}</p>
    @endif

    <div class="card">
        <dl>
            @forelse ($submission->payload as $field => $value)
                <dt>{{ \Illuminate\Support\Str::of($field)->replace(['_', '-'], ' ')->ucfirst() }}</dt>
                <dd>{{ is_scalar($value) ? (trim((string) $value) === '' ? __('(leer)') : $value) : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</dd>
            @empty
                <dt>{{ __('Felder') }}</dt>
                <dd>{{ __('(keine)') }}</dd>
            @endforelse
        </dl>
    </div>

    <div class="actions" style="margin-top: 24px">
        <form method="post" action="{{ route('echto.quarantine.release', $submission) }}">@csrf
            <button type="submit">{{ __('Freigeben') }}</button>
        </form>
        <form method="post" action="{{ route('echto.quarantine.discard', $submission) }}">@csrf @method('DELETE')
            <button type="submit" class="secondary">{{ __('Verwerfen') }}</button>
        </form>
    </div>
    <p class="muted small" style="margin-top: 12px">{{ __('Freigeben sendet die Einsendung so weiter, wie das Formular es sonst getan hätte. Verwerfen löscht sie.') }}</p>
@endsection
