<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Ustawienia Facebook</title>
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
      <div class="title">
        <a href="/admin/settings" class="avatar">
          <img src="{{ $user->avatar() }}">
        </a>
        <h1>Facebook</h1>
      </div>
    </nav>
    

    <ol class="list">
      <li class="separator">Podłączone konto</li>
      <li class="center">
        <img class="avatar" src="{{ $user_fb['picture']['url'] }}">
        <span class="margin__left">
          <div>{{ $user_fb['name'] }}</div>
          <small>{{ $user_fb['id'] }}</small>
        </span>
      </li>

      <li class="separator">Aktywna strona</li>
      <li class="center">
        <img class="avatar" src="{{ $page['picture']['url'] }}">
        <span class="margin__left">
          <div>{{ $page['name'] }}</div>
          <small>{{ $page['id'] }}</small>
        </span>
      </li>
    </ol>

    <ol class="list list--settings">
      <a href="/admin/settings/facebook/pages">
        <li class="clickable">
          <img class="icon" src="/img/icons/switch.svg"> Przełącz stronę
        </li>
      </a>
      <a href="/admin/settings/facebook/unlink">
        <li class="clickable">
          <img class="icon" src="/img/icons/logout.svg"> Odłącz konto
        </li>
      </a>
    </ol>

  </main>

  <script src="/js/admin.js"></script>
</body>
</html>