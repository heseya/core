@extends('admin/layout')

@section('title', 'Nowe konto')

@section('buttons')

@endsection

@section('content')
<form method="post">
    @csrf

    <div class="field">
        <label class="label" for="name">Imię i nazwisko</label>
        <div class="control">
            <input name="name" class="input @error('name') is-danger @enderror" required autocomplete="off" value="{{ old('name') }}">
        </div>
        @error('name')
            <p class="help is-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <label class="label" for="email">E-mail</label>
        <div class="control">
            <input name="email" class="input @error('email') is-danger @enderror" required autocomplete="off" value="{{ old('email') }}">
        </div>
        @error('email')
            <p class="help is-danger">{{ $message }}</p>
        @enderror
    </div>

    <small>Hasło zostawnie wygenerowanie automatycznie i wysłane na podanego e-maila</small><br><br>

    <button class="button is-black">Dodaj</button>
</form>
@endsection
