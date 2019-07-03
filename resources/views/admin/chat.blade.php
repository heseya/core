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
  <nav class="chat-nav">
    <a href="/admin/chat">
      <img src="/img/icons/back.svg">
    </a>
    <div>
      <div>Jakub Stężowski</div>
      <small>stezowskijakub@gmail.com</small>
    </div>
    <!-- <img src="/img/avatar.jpg" class="avatar"> -->
    <div></div>
  </nav>

  <div class="chat">
    @foreach($messages as $message)
      <div class="{{ $message['from']['id'] == $fb_page ? 'from' : '' }}">
        {{ $message['message'] }}
      </div>
    @endforeach
  </div>

  <!-- <form class="response">
    <textarea placeholder="Napisz coś miłego..."></textarea>
    <button>
      <img src="/img/icons/send.svg">
    </button>
  </form> -->

</body>
</html>