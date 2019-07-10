<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Konfiguracja e-mail</title>
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
      <h1>Konfiguracja e-mail</h1>
      <a href="/admin/settings" class="avatar">
        <img src="{{ $user->avatar() }}">
      </a>
    </nav>
    
    <form method="post">
      <div class="grid grid--2">
        <div>
          <p>Serwer poczty przychodzącej (IMAP)</p>
          <div class="input sto">
            <input type="text" name="from-user" value="{{ $old['to']['user'] }}" placeholder="Użytkownik">
          </div>
          <div class="input sto">
            <input type="password" name="from-user" placeholder="Hasło">
          </div>
          <div class="input sto">
            <input type="text" name="from-user" value="{{ $old['to']['host'] }}" placeholder="Adres serwera">
          </div>
          <div class="input sto">
            <input type="number" name="from-user" value="{{ $old['to']['port'] }}" placeholder="Port">
          </div>
        </div>
        <div>
          <p>Serwer poczty wychodzącej (SMPT)</p>
          <div class="input sto">
            <input type="text" name="from-user" value="{{ $old['from']['user'] }}" placeholder="Użytkownik">
          </div>
          <div class="input sto">
            <input type="password" name="from-user" placeholder="Hasło">
          </div>
          <div class="input sto">
            <input type="text" name="from-user" value="{{ $old['from']['host'] }}" placeholder="Adres serwera">
          </div>
          <div class="input sto">
            <input type="number" name="from-user" value="{{ $old['from']['port'] }}" placeholder="Port">
          </div>
        </div>
      </div>
      <button>Zapisz</button>
    </form>

  </main>

  <script src="/js/admin.js"></script>
</body>
</html>
