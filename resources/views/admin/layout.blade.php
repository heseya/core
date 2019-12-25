<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ mix('css/admin.css') }}">
    <title>@yield('title') - {{ Config::get('app.name') }}</title>
</head>
<body>

    <nav class="nav">
        <div class="logo">
            <img src="/img/logo.png">
        </div>

        <a href="/admin/orders">
            <img class="icon" src="/img/icons/orders.svg">
        </a>
        <a href="/admin/products">
            <img class="icon" src="/img/icons/products.svg">
        </a>
        <a href="/admin/items">
            <img class="icon" src="/img/icons/chest.svg">
        </a>
    </nav>

    <main class="main">
        <nav class="top-nav">
            <div class="title">
                <a href="/admin/settings" class="avatar">
                    <img src="{{ $user->avatar() }}">
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

    <script src="{{ mix('js/admin.js') }}"></script>
</body>
</html>
