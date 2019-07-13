@extends('mail/layout')

@section('title', 'Twoje dane logowania do panelu')

@section('greeting', 'Hej')

@section('content')
  <tr>
    <td height="20"></td>
  </tr>
  <tr>
    <td align="left">
      <h2 style="color: #000000; margin: 0; text-transform: uppercase; font-size: 15px; padding: 0 20px;">
        E-mail: {{ $email }} <br>
        Hasło: {{ $password }}
      </h2>
    </td>
  </tr>
@endsection