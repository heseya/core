<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Admin</title>
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
    </nav>

    <ol id="orders" class="list list--orders"></ol>

    <div class="loader--warper">
      <div class="loader"></div>
    </div>

  </main>

  <script src="/js/admin.js"></script>
</body>
</html>
