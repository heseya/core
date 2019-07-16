@extends('admin/layout')

@section('title', 'Ustawienia')

@section('buttons')

@endsection

@section('content')
  <ol class="list list--settings">
    <li class="center">
      <img class="avatar" src="{{ $user->avatar() }}">
      <span>
        <div>{{ $user['name'] }}</div>
        <small>{{ $user['email'] }}</small>
      </span>
    </li>

    <!-- <li class="clickable" onclick="darkMode()">
      <img class="icon" src="/img/icons/moon.svg">Tryb ciemny
    </li> -->

    <!-- <a href="/admin/settings/notifications">
      <li class="clickable">
        <img class="icon" src="/img/icons/alarm.svg">Powiadomienia
      </li>
    </a> -->

    <li class="separator">Ustawienia sklepu</li>
    <a href="/admin/settings/delivery">
      <li class="clickable">
        <img class="icon" src="/img/icons/delivery.svg">Dostawa
      </li>
    </a>
    <a href="/admin/settings/email">
      <li class="clickable">
        <img class="icon" src="/img/icons/email.svg">E-mail
      </li>
    </a>
    <a href="/admin/settings/facebook">
      <li class="clickable">
        <img class="icon" src="/img/icons/facebook.svg">Facebook
      </li>
    </a>
    <a href="/admin/settings/accounts">
      <li class="clickable">
        <img class="icon" src="/img/icons/accounts.svg">Dostęp do panelu
      </li>
    </a>

    <li class="separator">Inne</li>
    <a href="https://gravatar.com/emails" target="_blank">
      <li class="clickable">
        <img class="icon" src="/img/icons/joke.svg">Zmień avatar
      </li>
    </a>
    <a href="/admin/settings/info">
      <li class="clickable">
        <img class="icon" src="/img/icons/info.svg">Informacje o systemie
      </li>
    </a>
    <a href="/admin/logout">
      <li class="clickable">
        <img class="icon" src="/img/icons/logout.svg">Wyloguj się
      </li>
    </a>
  </ol>
@endsection

@section('scripts')
  <script></script>
@endsection
