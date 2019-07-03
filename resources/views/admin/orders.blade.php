<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Zamówenia</title>
  <link rel="stylesheet" href="/css/app.css">
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
      <h1>Zamówienia</h1>
      <a href="/admin/settings" class="avatar">
        <img src="/img/avatar.jpg">
      </a>
    </nav>

    <ol id="orders" class="list list--orders"></ol>
  </main>

  <!-- <div id="splashscreen" class="splashscreen"></div> -->
  <script src="/js/admin.js"></script>
</body>
</html>
