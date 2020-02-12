<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ mix('css/admin.css') }}">
    <title>@yield('title') - {{ Config::get('app.name') }}</title>
    @stack('head')
</head>
<body>

    <nav class="nav">
        <div class="logo">
            <img src="/img/logo.png">
        </div>

        <a href="/admin/products">
            <img class="icon" src="/img/icons/products.svg">
            <span class="label animated faster fadeInLeft">Asortyment</span>
        </a>
        <a href="/admin/orders">
            <img class="icon" src="/img/icons/orders.svg">
            <span class="label animated faster fadeInLeft">Zamówienia</span>
        </a>
        <a href="/admin/chat">
            <img class="icon" src="/img/icons/chat.svg">
            <span class="label animated faster fadeInLeft">Konwersajce</span>
        </a>
        <a href="/admin/pages">
            <img class="icon" src="/img/icons/copy.svg">
            <span class="label animated faster fadeInLeft">Strony</span>
        </a>
        <a href="/admin/stats">
            <img class="icon" src="/img/icons/chart.svg">
            <span class="label animated faster fadeInLeft">Statystyki</span>
        </a>
    </nav>

    <main class="main">
        <nav class="top-nav">
            <div class="title is-marginless">
                <a href="/admin/settings" class="avatar">
                    <img src="{{ Auth::user()->avatar() }}">
                </a>
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

    <script src="{{ mix('js/toast.js') }}"></script>
    <script src="{{ mix('js/admin.js') }}"></script>
</body>
</html>
