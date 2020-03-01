<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Logowanie</title>
    <link rel="stylesheet" href="{{ mix('css/admin.css') }}">
</head>
<body>
    <div class="columns is-marginless">
        <div class="column is-half is-paddingless">
            <div class="hero is-fullheight">
                <div class="hero-body">
                    <div class="container">
                        <div class="columns is-centered">
                            <div class="column is-8">
                                <h1 class="title is-3">Logowanie</h1>
                                <form method="post">
                                    @csrf

                                    <div class="field">
                                        <label class="label" for="error">E-mail</label>
                                        <div class="control">
                                            <input name="email" type="email" class="input @error('email') is-danger @enderror" required value="{{ old('email') }}" autofocus>
                                        </div>
                                        @error('email')
                                            <p class="help is-danger">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="field">
                                        <label class="label" for="error">Hasło</label>
                                        <div class="control">
                                            <input name="password" type="password" class="input @error('password') is-danger @enderror" required value="{{ old('password') }}">
                                        </div>
                                        @error('password')
                                            <p class="help is-danger">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="checkbox">
                                        <label for="remember">
                                            <input name="remember" id="remember" type="checkbox" class="checkbox">
                                            Zapamiętaj mnie
                                        </label>
                                    </div>
                                    <br><br>

                                    <div class="buttons">
                                        <button type="submit" class="button is-black">Zaloguj się</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="column is-half is-hidden-mobile is-paddingless">
            <div class="hero is-fullheight has-image"></div>
        </div>
    </div>
</body>
</html>
