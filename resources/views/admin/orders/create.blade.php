@extends('admin/layout')

@section('title', 'Nowe zamówienie')

@section('buttons')

@endsection

@section('content')
<form method="post">
  @csrf

  <div class="grid grid--2">
    <div>
      <p>Dane zamówienia</p>
      <div class="input sto">
        <input type="text" name="id" placeholder="Numer">
      </div>
      <div class="input sto">
        <input type="email" name="email" placeholder="E-mail">
      </div>
    </div>
    <div>
      <p>Dane adresowe</p>
      <div class="input sto">
        <input type="text" name="name" placeholder="Imię i nazwisko">
      </div>
      <div class="input sto">
        <input type="text" name="address" placeholder="Adres">
      </div>
      <div class="input sto">
        <input type="text" name="address" placeholder="Kod pocztowy">
      </div>
      <div class="input sto">
        <input type="text" name="address" placeholder="Miasto">
      </div>
    </div>
    <div>
      <br>
      <button class="button sto sto-mobile">Zapisz</button>
    </div>
  </div>
</form>
@endsection
