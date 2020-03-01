@extends('mail.layout')

@section('greeting', 'Hej')

@section('title', 'Twoje dane logowania do panelu')

@section('content')
<tr>
    <td height="30"></td>
</tr>
<tr>
    <td align="left">
        <h2 style="color: #000000; margin: 0; font-size: 13px; padding: 0 20px;">
            E-mail: <span style="color: #000000; text-decoration: none;">{{ $email }}</span><br>
            Hasło: {{ $password }}
        </h2>

        <p style="padding: 0 20px;">
            <a href="{{ route('login') }}">Zaloguj się</a>
        </p>
    </td>
</tr>
@endsection
