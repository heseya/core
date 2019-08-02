<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="/css/admin.css">
  <link rel="manifest" href="/manifest.json">

  <title>{{ $name }}</title>
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

  <nav class="product-nav">
    <a href="/admin/products">
      <img src="/img/icons/back.svg">
    </a>

    <div class="product-nav__photos">
      <img src="https://source.unsplash.com/collection/1085173">
    </div>
  </nav>

  <main id="main" class="product">
    <div class="details">
      <div class="content">
        <h1>{{ $name }}</h1>
        <small>200 zł</small>
        <p>{{ $description }}</p>
      </div>
    </div>

  </main>
  <script src="/js/admin.js"></script>
</body>
</html>
