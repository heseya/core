@extends('admin/layout')

@section('title', 'Nowe konto')

@section('buttons')
  
@endsection

@section('content')
<form method="post">
  @csrf

  <div class="grid grid--2">
    <div>
      <div class="input sto">
        <input type="text" name="name" placeholder="Imię i nazwisko" required>
      </div>
      <div class="input sto">
        <input type="email" name="email" placeholder="E-mail" required>
      </div>
      <small>Hasło zostawnie wygenerowanie automatycznie i wysłane na podanego e-maila</small><br><br>
      <button class="button">Dodaj</button>
    </div>
  </div>
</form>
@endsection

@section('scripts')
  
@endsection

