<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Produkty</title>
  <link rel="stylesheet" href="/css/admin.css">
</head>
<body>
  <nav class="nav">
    <div class="logo">
      <img src="/img/logo.png">
    </div>

    <a href="/admin/products" class="nav--products nav--products__selected"></a>
    <a href="/admin/orders" class="nav--orders"></a>
    <a href="/admin/chat" class="nav--chat"></a>
  </nav>

  <main>
    <nav class="top-nav">
      <div class="title">
        <a href="/admin/settings" class="avatar">
          <img src="{{ $user->avatar() }}">
        </a>
        <h1>Asortyment</h1>
      </div>
    </nav>

    <div class="products-list">
      <a href="/admin/products/1" class="product">
        <img src="/img/avatar.jpg">
        <div class="details">
          <div>200zł</div>
          <div>Naszyjnik</div>
        </div>
        <div class="name">Automaton Grand</div>
      </a>
      <a href="/admin/products/1" class="product">
        <img src="/img/avatar.jpg">
        <div class="details">
          <div>200zł</div>
          <div>Naszyjnik</div>
        </div>
        <div class="name">Automaton Grand</div>
      </a>
      <a href="/admin/products/1" class="product">
        <img src="/img/avatar.jpg">
        <div class="details">
          <div>200zł</div>
          <div>Naszyjnik</div>
        </div>
        <div class="name">Automaton Grand</div>
      </a>
      <a href="/admin/products/1" class="product">
        <img src="/img/avatar.jpg">
        <div class="details">
          <div>200zł</div>
          <div>Naszyjnik</div>
        </div>
        <div class="name">Automaton Grand</div>
      </a>
      <a href="/admin/products/1" class="product">
        <img src="/img/avatar.jpg">
        <div class="details">
          <div>200zł</div>
          <div>Naszyjnik</div>
        </div>
        <div class="name">Automaton Grand</div>
      </a>
      <a href="/admin/products/1" class="product">
        <img src="/img/avatar.jpg">
        <div class="details">
          <div>200zł</div>
          <div>Naszyjnik</div>
        </div>
        <div class="name">Automaton Grand</div>
      </a>
      <a href="/admin/products/1" class="product">
        <img src="/img/avatar.jpg">
        <div class="details">
          <div>200zł</div>
          <div>Naszyjnik</div>
        </div>
        <div class="name">Automaton Grand</div>
      </a>
      <a href="/admin/products/1" class="product">
        <img src="/img/avatar.jpg">
        <div class="details">
          <div>200zł</div>
          <div>Naszyjnik</div>
        </div>
        <div class="name">Automaton Grand</div>
      </a>
      <a href="/admin/products/1" class="product">
        <img src="/img/avatar.jpg">
        <div class="details">
          <div>200zł</div>
          <div>Naszyjnik</div>
        </div>
        <div class="name">Automaton Grand</div>
      </a>
    </div>
  </main>

  <script src="/js/admin.js"></script>
</body>
</html>