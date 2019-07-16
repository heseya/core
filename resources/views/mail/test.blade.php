@extends('mail/layout')

@section('greeting', 'Hej')

@section('title', 'To jest wiadomość testowa')

@section('content')
  <tr>
    <td height="20"></td>
  </tr>
  <tr>
    <td align="left">
      <h2 style="color: #000000; margin: 0; font-size: 13px; padding: 0 20px;">
        Jeśli czytasz tego maila to system wysyłki prawdopodobnie działa.
      </h2>
    </td>
  </tr>
@endsection