@extends('admin.layout')

@section('title', 'Ustawienia')

@section('buttons')

@endsection

@section('content')
<ol class="list list--settings">
    {{-- <li class="clickable" onclick="darkMode()">
        <img class="icon" src="/img/icons/moon.svg">Tryb ciemny
    </li> --}}

    {{-- <a href="/admin/settings/notifications">
        <li class="clickable">
            <img class="icon" src="/img/icons/alarm.svg">Powiadomienia
        </li>
    </a> --}}

    @can('manageStore')
    <li class="separator">Sklep</li>
    <a href="{{ route('categories') }}">
        <li class="clickable">
            <img class="icon" src="/img/icons/list.svg">Kategorie
        </li>
    </a>
    <a href="{{ route('brands') }}">
        <li class="clickable">
            <img class="icon" src="/img/icons/shield.svg">Marki
        </li>
    </a>
    <a href="{{ route('email') }}">
        <li class="clickable">
            <img class="icon" src="/img/icons/email.svg">E-mail
        </li>
    </a>
    {{-- <a href="/admin/settings/delivery">
        <li class="clickable">
            <img class="icon" src="/img/icons/delivery.svg">Dostawa
        </li>
    </a> --}}
    @endcan

    <li class="separator">Panel</li>
    {{-- <a href="/admin/settings/facebook">
        <li class="clickable">
            <img class="icon" src="/img/icons/facebook.svg">Facebook
        </li>
    </a> --}}
    <a href="{{ route('users') }}">
        <li class="clickable">
            <img class="icon" src="/img/icons/accounts.svg">Dostęp do panelu
        </li>
    </a>

    <li class="separator">Integracje</li>
    <a href="{{ route('furgonetka') }}">
        <li class="clickable">
            <img class="icon" src="/img/icons/delivery.svg">Furgonetka
        </li>
    </a>

    <li class="separator">Inne</li>
    <a href="{{ route('docs') }}">
        <li class="clickable">
            <img class="icon" src="/img/icons/book.svg">Dokumentacja API
        </li>
    </a>
    <a href="{{ route('info') }}">
        <li class="clickable">
            <img class="icon" src="/img/icons/info.svg">Informacje o systemie
        </li>
    </a>
    <a href="{{ route('logout') }}">
        <li class="clickable">
            <img class="icon" src="/img/icons/logout.svg">Wyloguj się
        </li>
    </a>
</ol>
@endsection
