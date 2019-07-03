<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Facebook</title>
  <link rel="stylesheet" href="/css/app.css">
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
      <h1>Facebook</h1>
      <a href="/admin/settings" class="avatar">
        <img src="/img/avatar.jpg">
      </a>
    </nav>

    <ol class="list">
      <li class="separator">Podłączone konto</li>
      <li class="center">
        <img class="round" src="{{ $user['picture']['url'] }}">
        <span class="margin__left">
          <div>{{ $user['name'] }}</div>
          <small>{{ $user['id'] }}</small>
        </span>
      </li>

      <li class="separator">Aktywna strona</li>
      <li class="center">
        <img class="round" src="{{ $page['picture']['url'] }}">
        <span class="margin__left">
          <div>{{ $page['name'] }}</div>
          <small>{{ $page['id'] }}</small>
        </span>
      </li>
    </ol>

    <ol class="list list--settings">
      <a href="/admin/facebook/pages">
        <li class="clickable">
          <img class="icon" src="/img/icons/switch.svg"> Przełącz stronę
        </li>
      </a>
      <a href="/admin/facebook/unlink">
        <li class="clickable">
          <img class="icon" src="/img/icons/logout.svg"> Odłącz konto
        </li>
      </a>
    </ol>

  </main>

  <script src="/js/admin.js"></script>
</body>
</html>