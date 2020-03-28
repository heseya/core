@extends('admin.layout')

@section('title', $user->name)

@section('buttons')

@endsection

@section('content')
<form action="{{ route('users.rbac', $user) }}" method="post">
    @csrf

    <ol class="list list--settings">
        <li class="center">
            <img class="avatar" src="{{ $user->avatar }}">
            <span>
                <div>{{ $user->name }}</div>
                <small>{{ $user->email }}</small>
            </span>
        </li>

        <li class="separator">Uprawnienia</li>
        <li class="separator">Produkty</li>
        <li><label class="checkbox">
            <input name="perms[viewProducts]" type="checkbox" {{ $user->can('viewProducts') ? 'checked' : '' }}>
            Przeglądanie
        </label></li>
        <li><label class="checkbox">
            <input name="perms[manageProducts]" type="checkbox" {{ $user->can('manageProducts') ? 'checked' : '' }}>
            Zarządzanie
        </label></li>
        <li><label class="checkbox">
            <input name="perms[createProducts]" type="checkbox" {{ $user->can('createProducts') ? 'checked' : '' }}>
            Tworzenie
        </label></li>

        <li class="separator">Zamówienia</li>
        <li><label class="checkbox">
            <input name="perms[viewOrders]" type="checkbox" {{ $user->can('viewOrders') ? 'checked' : '' }}>
            Przeglądanie
        </label></li>
        <li><label class="checkbox">
            <input name="perms[manageOrders]" type="checkbox" {{ $user->can('manageOrders') ? 'checked' : '' }}>
            Zarządzanie
        </label></li>
        <li><label class="checkbox">
            <input name="perms[createOrders]" type="checkbox" {{ $user->can('createOrders') ? 'checked' : '' }}>
            Tworzenie
        </label></li>

        <li class="separator">Konwersacje</li>
        <li><label class="checkbox">
            <input name="perms[viewChats]" type="checkbox" {{ $user->can('viewChats') ? 'checked' : '' }}>
            Przeglądanie
        </label></li>
        <li><label class="checkbox">
            <input name="perms[replyChats]" type="checkbox" {{ $user->can('replyChats') ? 'checked' : '' }}>
            Odpowiedź
        </label></li>
        <li><label class="checkbox">
            <input name="perms[createChats]" type="checkbox" {{ $user->can('createChats') ? 'checked' : '' }}>
            Tworzenie
        </label></li>

        <li class="separator">Strony</li>
        <li><label class="checkbox">
            <input name="perms[viewPages]" type="checkbox" {{ $user->can('viewPages') ? 'checked' : '' }}>
            Przeglądanie
        </label></li>
        <li><label class="checkbox">
            <input name="perms[managePages]" type="checkbox" {{ $user->can('managePages') ? 'checked' : '' }}>
            Zarządzanie
        </label></li>

        <li class="separator">Administracja</li>
        <li><label class="checkbox">
            <input name="perms[manageUsers]" type="checkbox" {{ $user->can('manageUsers') ? 'checked' : '' }}>
            Zarządzanie użytkownikami
        </label></li>
        <li><label class="checkbox">
            <input name="perms[manageStore]" type="checkbox" {{ $user->can('manageStore') ? 'checked' : '' }}>
            Zarządzanie sklepem
        </label></li>
    </ol>

    @can('manageUsers')
        <br>
        <button class="button is-black">Zapisz</button>
    @endcan

</form>
@endsection
