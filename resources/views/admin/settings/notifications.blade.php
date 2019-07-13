<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Powiadomienia</title>
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
        <h1>Powiadomienia</h1>
      </div>
    </nav>
    

    <ol class="list list--settings">
      <li>
        <input name="new" id="new" class="switch" type="checkbox">
        <label for="new">Nowe zamówienie</label>
      </li>
    </ol>
    <ol class="list list--settings">
      <li>
        <input name="new" id="new" class="switch" type="checkbox">
        <label for="new">Nowa wiadomość</label>
      </li>
    </ol>
  </main>

  <script src="/js/admin.js"></script>
</body>
</html>