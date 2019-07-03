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
    
    <ol class="list list--settings">
      <li>Nie jesteś połączony.</li>
      <a href="/admin/facebook/login">
        <li class="clickable">
          <img class="icon" src="/img/icons/facebook.svg">
          Połącz z kontem Facebook
        </li>
      </a>
    </ol>

  </main>

  <script src="/js/admin.js"></script>
</body>
</html>
