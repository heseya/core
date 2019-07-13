<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Dostęp do panelu</title>
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
        <h1>Dostęp do panelu</h1>
      </div>
      <div>
        <a href="/admin/settings/accounts/add" class="top-nav--button">
          <img class="icon" src="/img/icons/plus.svg">
        </a>
      </div>
    </nav>

    <ol class="list">
      @foreach($accounts as $user)
        <li class="center">
          <img class="avatar" src="{{ $user->avatar() }}">
          <span class="margin__left">
            <div>{{ $user['name'] }}</div>
            <small>{{ $user['email'] }}</small>
          </span>
        </li>
      @endforeach
    </ol>

  </main>
  <script src="/js/admin.js"></script>
</body>
</html>