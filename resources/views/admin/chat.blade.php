<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <a href="/admin/orders" class="nav--orders"></a>
    <a href="/admin/chat" class="nav--chat nav--chat__selected"></a>
  </nav>

  <main>
    <nav class="top-nav">
      <h1>Konwersacje</h1>
    </nav>

    <ol class="list list--chat">
      <li class="separator">
        Dzisiaj
      </li>
      <a href="/admin/chat/1">
        <li>
          <div class="avatar">
            <img src="/img/avatar.jpg">
          </div>
          <div>
            <div>Szymon Grabowski</div>
            <small>Kiedy moje zamówienie?</small>
          </div>
        </li>
      </a>
      <li>
        <div class="avatar">
          <img src="/img/icons/email.svg">
        </div>
        <div>
          <div>stezowskijakub@gmail.com</div>
          <small class="one-line">Hej, Wyślijcie w foliowym worku...</small>
        </div>
      </li>
    </ol>
  </main>
</body>
</html>