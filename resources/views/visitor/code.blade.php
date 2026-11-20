@extends('echto::visitor.layout')

@section('title', __('Code eingeben'))

@section('content')
    <h1>{{ __('Bitte bestätigen Sie Ihre Anmeldung') }}</h1>
    <p class="muted">{{ __('Wir haben Ihnen einen Code mit sechs Ziffern per E-Mail gesendet. Geben Sie ihn hier ein; danach melden Sie sich noch einmal an.') }}</p>
    <form method="post" action="{{ $action }}" class="card">
        @csrf
        <label for="echto-code">{{ __('Code') }}</label>
        <input id="echto-code" name="code" inputmode="numeric" autocomplete="one-time-code" required autofocus @if ($error) aria-invalid="true" aria-describedby="echto-code-error" @endif>
        @if ($error)
            <p id="echto-code-error" class="error" role="alert">{{ $error }}</p>
        @endif
        <button type="submit">{{ __('Bestätigen') }}</button>
    </form>
@endsection
