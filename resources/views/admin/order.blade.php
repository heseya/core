<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Zamówenie {{ $code }}</title>
  <link rel="stylesheet" href="/css/admin.css">
</head>
<body>

  <nav class="nav">
    <div class="logo">
      <img src="/img/logo.png">
    </div>

    <a href="/admin/products" class="nav--products"></a>
    <a href="/admin/orders" class="nav--orders nav--orders__selected"></a>
    <a href="/admin/chat" class="nav--chat"></a>
  </nav>

  <main>
    <nav class="top-nav">
      <div class="title">
        <a href="/admin/settings" class="avatar">
          <img src="{{ $user->avatar() }}">
        </a>
        <h1>Zamówienie {{ $code }}</h1>
      </div>
    </nav>

    <div class="order">
      <div>
        <div class="separator">Adres dostawy</div><br>
        <div>Wojtek Kowalski</div>
        <div>Gdańska 82/1</div>
        <div>82-200 Bydgoszcz</div>
      </div>
    </div>
  </main>

  <!-- <div id="splashscreen" class="splashscreen"></div> -->
  <script src="/js/admin.js"></script>
</body>
</html>
