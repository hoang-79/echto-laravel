@extends('echto::visitor.layout')

@section('title', __('Code ungültig'))

@section('content')
    <h1>{{ __('Dieser Code gilt nicht mehr.') }}</h1>
    <p class="muted">{{ __('Er ist abgelaufen oder wurde zu oft falsch eingegeben. Bitte melden Sie sich noch einmal an; dann kommt ein neuer Code.') }}</p>
    <p><a class="button secondary" href="{{ url('/') }}">{{ __('Zur Website') }}</a></p>
@endsection
