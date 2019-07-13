<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Konwersacje</title>
  <link rel="stylesheet" href="/css/admin.css">
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
      <div class="title">
        <a href="/admin/settings" class="avatar">
          <img src="{{ $user->avatar() }}">
        </a>
        <h1>Konwersacje</h1>
      </div>
    </nav>

    <ol class="list list--chat">
      <!-- <li class="separator">
        Dzisiaj
      </li> -->
      @foreach($chats as $chat)
        <a href="/admin/chat/{{ $chat['id'] }}">
          <li class="clickable">
            <div class="avatar">
              <img src="/img/avatar.jpg">
            </div>
            <div>
              <div class="{{ $chat['unread_count'] > 0 ? 'unread' : '' }}">{{ $chat['participants'][0]['name'] }}</div>
              <small>{{ $chat['snippet'] }}</small>
            </div>
          </li>
        </a>
      @endforeach

    </ol>
  </main>

  <script src="/js/admin.js"></script>
</body>
</html>