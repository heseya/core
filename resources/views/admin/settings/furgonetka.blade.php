@extends('admin/layout')

@section('title', 'Furgonetka')

@section('buttons')

@endsection

@section('content')
<ol class="list list--settings">
    <li class="separator">Webhook</li>
    <li>System odbiera powiadomienia w formacie JSON pod adresem {{ Config::get('app.url') }}/furgonetka/webhook</li>
    <li>Sól: {{ Config::get('furgonetka.webhook_salt') }}</li>
    <a href="https://furgonetka.pl/konto/powiadomienia" target="_blank">
        <li class="clickable">
            <img class="icon" src="/img/icons/settings.svg">Ustawienia
        </li>
    </a>
</ol>
@endsection

@section('scripts')

@endsection
