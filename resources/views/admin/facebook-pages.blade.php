<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Facebook</title>
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
      <h1>Wybierz stronę</h1>
      <a href="/admin/settings" class="avatar">
        <img src="/img/avatar.jpg">
      </a>
    </nav>

    <ol class="list">
      @foreach($pages as $page)
        <a href="/admin/facebook/set-page/{{ $page['access_token'] }}">
          <li class="center clickable">
            <img class="round" src="{{ $page['picture']['url'] }}">
            <span class="margin__left">
              <div>{{ $page['name'] }}</div>
              <small>{{ $page['id'] }}</small>
            </span>
          </li>
        </a>
      @endforeach
    </ol>

  </main>

  <script src="/js/admin.js"></script>
</body>
</html>