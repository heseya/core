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

        <a href="/admin/products" class="nav--products"></a>
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
                <button class="button button--second sto" type="button" onclick="closeModal()">Nie</button>
                <button class="button sto">Tak</button>
            </div>
        </form>
        <div class="modal__bg" onclick="closeModal()"></div>
    </div>

    <script src="{{ mix('js/admin.js') }}"></script>
    @yield('scripts')
</body>
</html>
