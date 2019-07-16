@extends('admin/layout')

@section('title', 'Dostęp do panelu')

@section('buttons')

@endsection

@section('content')
<form method="post">
  @csrf

  <div class="grid grid--2">
    <div>
      <p>Serwer poczty przychodzącej (IMAP)</p>
      <div class="input sto">
        <input type="text" name="to-user" value="{{ $old['to']['user'] }}" placeholder="Użytkownik">
      </div>
      <div class="input sto">
        <input type="password" name="to-password" value="{{ $old['to']['password'] }}" placeholder="Hasło">
      </div>
      <div class="input sto">
        <input type="text" name="to-host" value="{{ $old['to']['host'] }}" placeholder="Adres serwera">
      </div>
      <div class="input sto">
        <input type="number" name="to-port" value="{{ $old['to']['port'] }}" placeholder="Port" required>
      </div>
    </div>
    <div>
      <p>Serwer poczty wychodzącej (SMPT)</p>
      <div class="input sto">
        <input type="text" name="from-user" value="{{ $old['from']['user'] }}" placeholder="Użytkownik">
      </div>
      <div class="input sto">
        <input type="password" name="from-password" value="{{ $old['from']['password'] }}" placeholder="Hasło">
      </div>
      <div class="input sto">
        <input type="text" name="from-host" value="{{ $old['from']['host'] }}" placeholder="Adres serwera">
      </div>
      <div class="input sto">
        <input type="number" name="from-port" value="{{ $old['from']['port'] }}" placeholder="Port" required>
      </div>
    </div>
    <div>
      <button class="button">Zapisz</button>
    </div>
  </div>
</form>
@endsection

@section('scripts')
  
@endsection