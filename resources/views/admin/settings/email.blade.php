<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>E-mail</title>
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
      <h1>E-mail</h1>
      <a href="/admin/settings" class="avatar">
        <img src="{{ $user->avatar() }}">
      </a>
    </nav>
    
    <ol class="list list--settings">
      <li class="center">
        <img class="avatar" src="//www.gravatar.com/avatar/{{ $gravatar }}?d=retro">
        <span>
          <div>{{ $email }}</div>
          <small>Avatar pobierany jest z <a href="//gravatar.com" target="_blank">gravatar.com</a></small>
        </span>
      </li>
      <a href="/admin/settings/email/config">
        <li class="clickable">
          <img class="icon" src="/img/icons/settings.svg">Ustawienia serwera
        </li>
      </a>
    </ol>

  </main>

  <script src="/js/admin.js"></script>
</body>
</html>
