<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="/css/admin.css">
  <link rel="manifest" href="/manifest.json">

  <title>@yield('title')</title>
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

  <main id="main">
    <nav class="top-nav">
      <div class="title">
        <a href="/admin/settings" class="avatar">
          <img src="{{ $user->avatar() }}">
        </a>
        <h1>@yield('title')</h1>
      </div>
      <div>
        @yield('buttons')
      </div>
    </nav>

    @yield('content')

  </main>
  <script src="/js/admin.js"></script>
  @yield('scripts')
</body>
</html>
