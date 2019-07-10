<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Ustawienia</title>
  <link rel="stylesheet" href="/css/admin.css">
</head>
<body>
  <nav class="nav">
    <div class="logo">
      <img src="/img/logo.png">
    </div>

    <a href="/admin/products" class="nav--products"></a>
    <a href="/admin/orders" class="nav--orders"></a>
    <a href="/admin/chat" class="nav--chat"></a>
  </nav>

  <main>
    <nav class="top-nav">
      <h1>Ustawienia</h1>
      <a href="/admin/settings" class="avatar">
        <img src="{{ $user->avatar() }}">
      </a>
    </nav>

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
  </main>

  <script src="/js/admin.js"></script>
</body>
</html>