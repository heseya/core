<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login</title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body>

    <div class="login">
        <form method="POST" action="/admin/login">
        @csrf

        <div class="field">
            <label class="label" for="error">E-mail</label>
            <div class="control">
                <input name="email" type="email" class="input @error('email') is-danger @enderror" required value="{{ old('email') }}">
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

        <div class="buttons">
            <div></div>
            {{-- <a href="/admin/reset-password">Nie pamiętam hasła</a> --}}
            {{-- <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}> --}}
            <button type="submit">
                <img src="/img/icons/send2.svg">
            </button>
        </div>
        </form>
    </div>

</body>
</html>
