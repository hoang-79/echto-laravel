@extends('echto::visitor.layout')

@section('title', __('Bestätigt'))

@section('content')
    <h1>{{ __('Danke, Ihre Nachricht ist bestätigt.') }}</h1>
    <p class="muted">{{ __('Sie ist jetzt bei uns. Sie müssen nichts weiter tun.') }}</p>
    @if ($returnUrl)
        <p><a class="button secondary" href="{{ $returnUrl }}">{{ __('Zurück zur Website') }}</a></p>
    @endif
@endsection
