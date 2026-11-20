@extends('echto::visitor.layout')

@section('title', __('Nachricht bestätigen'))

@section('content')
    <h1>{{ __('Nachricht bestätigen') }}</h1>
    <p class="muted">{{ __('Bitte bestätigen Sie mit einem Klick, dass die Nachricht von Ihnen stammt. Danach ist sie bei uns.') }}</p>
    <form method="post" action="{{ $action }}">
        @csrf
        <button type="submit">{{ __('Nachricht bestätigen') }}</button>
    </form>
@endsection
