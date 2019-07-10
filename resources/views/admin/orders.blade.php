<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Zamówenia</title>
  <link rel="stylesheet" href="/css/admin.css">
</head>
<body>

  <nav id="sidebar" class="nav">
    <div id="logo" class="logo">
      <img src="/img/logo.png">
    </div>

    <span id="tabs"></span>
  </nav>

  <main id="main">
    <nav class="top-nav">
      <h1>Zamówienia</h1>
      <a href="/admin/settings" class="avatar">
        <img src="{{ $user->avatar() }}">
      </a>
    </nav>

    <ol id="orders" class="list list--orders"></ol>
  </main>

  <!-- <div id="splashscreen" class="splashscreen"></div> -->
  <script src="/js/admin.js"></script>
</body>
</html>
