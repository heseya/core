<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Admin</title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body onload="toBottom()">

    <nav class="chat-nav">
        <a href="/admin/chat">
            <img src="/img/icons/back.svg">
        </a>
        <div>
            <div>{{ $client['name'] }}</div>
            <small>{{ $type }}</small>
        </div>
        {{-- <img src="/img/avatar.jpg" class="avatar"> --}}
        <div style="width: 36px"></div>
    </nav>

    <div class="chat">
        @foreach($messages as $message)
        <div class="message message--{{ empty($message['user_id']) ? 'to' : 'from' }}">
            <div class="bubble">
                {{ $message['content'] }}
            </div>
            <div class="info">
                {{ empty($message['user_id']) ? '' : $message->user->name }}
            </div>
        </div>
        @endforeach
    </div>

    <form class="response">
        <input type="hidden">
        <textarea placeholder="Napisz coś miłego..."></textarea>
        <button>
        <img src="/img/icons/send.svg">
        </button>
    </form>

    <script src="/js/admin.js"></script>
</body>
</html>
