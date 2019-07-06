<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Resetowanie hasła</title>
  <link rel="stylesheet" href="/css/admin.css">
</head>
<body>

  <div class="login">
    <form method="POST" action="{{ route('password.email') }}">
      @csrf

      @if (session('status'))
        <div class="alert alert-success" role="alert">
          {{ session('status') }}
        </div>
      @endif

      @error('email')
        <span class="invalid-feedback" role="alert">
          <strong>{{ $message }}</strong>
        </span>
      @enderror

      <div class="input">
        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus placeholder="E-mail">
      </div>

      <div class="buttons">
        <a href="/admin/login">Wróć do logowania</a>
        <button type="submit">
          <img src="/img/icons/send2.svg">
        </button>
      </div>
    </form>
  </div>

</body>
</html>