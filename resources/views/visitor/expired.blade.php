@extends('echto::visitor.layout')

@section('title', __('Link ungültig'))

@section('content')
    @if ($used)
        <h1>{{ __('Dieser Link wurde schon benutzt.') }}</h1>
        <p class="muted">{{ __('Ihre Nachricht ist bestätigt oder wurde schon bearbeitet. Sie müssen nichts weiter tun.') }}</p>
    @else
        <h1>{{ __('Dieser Link gilt nicht mehr.') }}</h1>
        <p class="muted">{{ trans_choice('Er war eine Stunde gültig. Ihre Nachricht wurde nicht zugestellt. Bitte senden Sie sie noch einmal.|Er war :count Stunden gültig. Ihre Nachricht wurde nicht zugestellt. Bitte senden Sie sie noch einmal.', $hours, ['count' => $hours]) }}</p>
    @endif
    <p><a class="button secondary" href="{{ url('/') }}">{{ __('Zur Website') }}</a></p>
@endsection
