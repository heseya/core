<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Zamówenia</title>
  <link rel="stylesheet" href="/css/admin.css">
</head>
<body onload="updateOrders()">

  <nav class="nav">
    <div class="logo">
      <img src="/img/logo.png">
    </div>

    <a href="/admin/products" class="nav--products nav--products__selected"></a>
    <a href="/admin/orders" class="nav--orders"></a>
    <a href="/admin/chat" class="nav--chat"></a>
  </nav>

  <main id="main">
    <nav class="top-nav">
      <div class="title">
        <a href="/admin/settings" class="avatar">
          <img src="{{ $user->avatar() }}">
        </a>
        <h1>Zamówienia</h1>
      </div>
      <div>
        <a href="/admin/orders/add" class="top-nav--button">
          <img class="icon" src="/img/icons/plus.svg">
        </a>
      </div>
    </nav>

    <ol id="orders" class="list list--orders"></ol>
  </main>

  <!-- <div id="splashscreen" class="splashscreen"></div> -->
  <script src="/js/admin.js"></script>
</body>
</html>
