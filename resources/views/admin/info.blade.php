<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Informacje o systemie</title>
  <link rel="stylesheet" href="/css/app.css">
</head>
<body class="dark">

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
      <h1>Informacje o systemie</h1>
      <a href="/admin/settings" class="avatar">
        <img src="/img/avatar.jpg">
      </a>
    </nav>
    
    <ol class="list list--settings">
      <li>Heseya Shop System wersja {{ $version }}</li>
      <li>Icons made by Freepik from www.flaticon.com is licensed by CC 3.0 BY</li>
    </ol>

  </main>

  <script src="/js/admin.js"></script>
</body>
</html>
