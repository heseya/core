<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Nowe konto</title>
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
        <h1>Nowe konto</h1>
      </div>
    </nav>
    
    <form method="post">
      @csrf

      <div class="grid grid--2">
        <div>
          <div class="input sto">
            <input type="text" name="name" placeholder="Imię i nazwisko" required>
          </div>
          <div class="input sto">
            <input type="email" name="email" placeholder="E-mail" required>
          </div>
          <small>Hasło zostawnie wygenerowanie automatycznie i wysłane na podanego e-maila</small><br><br>
          <button class="button">Dodaj</button>
        </div>
      </div>
    </form>

  </main>

  <script src="/js/admin.js"></script>
</body>
</html>
