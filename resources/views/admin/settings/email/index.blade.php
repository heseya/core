@extends('admin.layout')

@section('title', 'E-mail')

@section('buttons')

@endsection

@section('content')
<ol class="list list--settings">
    <li class="center">
        <img class="avatar" src="//www.gravatar.com/avatar/{{ $gravatar }}?d=retro">
        <span>
            <div>{{ $name }}</div>
            <small>{{ $email }}</small>
        </span>
    </li>
    @if ($imap == false)
        <li>
            <img class="icon" src="/img/icons/warning.svg">
            <span>
                <div>Serwer nie posiada biblioteki IMAP!</div>
            </span>
        </li>
    @endif
    {{-- <a href="/admin/settings/email/config">
        <li class="clickable">
            <img class="icon" src="/img/icons/settings.svg">Ustawienia serwera
        </li>
    </a> --}}
    <a href="{{ route('email.test') }}">
        <li class="clickable">
            <img class="icon" src="/img/icons/email-send.svg">
            <span>
                <div>Test wysyłki</div>
                <small>Wyślij testową wiadomość na adres: {{ auth()->user()->email }}</small>
            </span>
        </li>
    </a>
</ol>
@endsection

@section('scripts')

@endsection
