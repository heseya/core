<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ mix('css/admin.css') }}">
    <title>@yield('title') - {{ config('app.name') }}</title>
    @stack('head')
</head>
<body>

    <nav class="nav">
        <div class="logo">
            <img src="/img/logo.png">
        </div>

        @can('viewProducts')
        <a href="{{ route('products') }}">
            <img class="icon" src="/img/icons/products.svg">
            <span class="label animated faster fadeInLeft">Asortyment</span>
        </a>
        @endcan
        @can('viewOrders')
        <a href="{{ route('orders') }}">
            <img class="icon" src="/img/icons/orders.svg">
            <span class="label animated faster fadeInLeft">Zamówienia</span>
        </a>
        @endcan
        @can('viewChats')
        <a href="{{ route('chats') }}">
            <img class="icon" src="/img/icons/chat.svg">
            <span class="label animated faster fadeInLeft">Konwersajce</span>
        </a>
        @endcan
        @can('viewPages')
        <a href="{{ route('pages') }}">
            <img class="icon" src="/img/icons/copy.svg">
            <span class="label animated faster fadeInLeft">Strony</span>
        </a>
        @endcan
        <a href="{{ route('settings') }}">
            <img class="icon" src="/img/icons/settings.svg">
            <span class="label animated faster fadeInLeft">Ustawienia</span>
        </a>
    </nav>

    <main class="main">
        <nav class="top-nav">
            <div class="title is-marginless">
                <h1>@yield('title')</h1>
            </div>
            <div class="buttons">
                @yield('buttons')
            </div>
        </nav>

        @yield('content')

    </main>

    <div id="modal-confirm" class="modal modal--hidden">
        <form id="modal-form" class="modal__body">
            <h3 id="modal-title"></h3>
            <div class="grid grid--two grid--no-margin">
                <button class="button" type="button" onclick="closeModal()">Nie</button>
                <button class="button is-black">Tak</button>
            </div>
        </form>
        <div class="modal__bg" onclick="closeModal()"></div>
    </div>

    <div id="modal-info" class="modal modal--hidden">
        <div class="modal__body">
            <p id="modal-content" class="content"></p>
        </div>
        <div class="modal__bg" onclick="closeModal()"></div>
    </div>

    <script src="{{ mix('js/toast.js') }}"></script>
    <script src="{{ mix('js/admin.js') }}"></script>
</body>
</html>
