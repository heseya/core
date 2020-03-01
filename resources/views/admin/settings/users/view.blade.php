@extends('admin/layout')

@section('title', $user->name)

@section('buttons')

@endsection

@section('content')
<form action="{{ route('users.rbac', $user->id) }}" method="post">
    @csrf

    <ol class="list list--settings">
        <li class="center">
            <img class="avatar" src="{{ $user->avatar() }}">
            <span>
                <div>{{ $user->name }}</div>
                <small>{{ $user->email }}</small>
            </span>
        </li>

        <li class="separator">Uprawnienia</li>
        <li class="separator">Produkty</li>
        <li><label class="checkbox">
            <input name="perms[viewProducts]" type="checkbox" @can('viewProducts') checked @endcan>
            Przeglądanie
        </label></li>
        <li><label class="checkbox">
            <input name="perms[manageProducts]" type="checkbox" @can('manageProducts') checked @endcan>
            Zarządzanie
        </label></li>
        <li><label class="checkbox">
            <input name="perms[createProducts]" type="checkbox" @can('createProducts') checked @endcan>
            Tworzenie
        </label></li>

        <li class="separator">Zamówienia</li>
        <li><label class="checkbox">
            <input name="perms[viewOrders]" type="checkbox" @can('viewOrders') checked @endcan>
            Przeglądanie
        </label></li>
        <li><label class="checkbox">
            <input name="perms[manageOrders]" type="checkbox" @can('manageOrders') checked @endcan>
            Zarządzanie
        </label></li>
        <li><label class="checkbox">
            <input name="perms[createOrders]" type="checkbox" @can('createOrders') checked @endcan>
            Tworzenie
        </label></li>

        <li class="separator">Konwersacje</li>
        <li><label class="checkbox">
            <input name="perms[viewChats]" type="checkbox" @can('viewChats') checked @endcan>
            Przeglądanie
        </label></li>
        <li><label class="checkbox">
            <input name="perms[replyChats]" type="checkbox" @can('replyChats') checked @endcan>
            Odpowiedź
        </label></li>
        <li><label class="checkbox">
            <input name="perms[createChats]" type="checkbox" @can('createChats') checked @endcan>
            Tworzenie
        </label></li>

        <li class="separator">Strony</li>
        <li><label class="checkbox">
            <input name="perms[viewPages]" type="checkbox" @can('viewPages') checked @endcan>
            Przeglądanie
        </label></li>
        <li><label class="checkbox">
            <input name="perms[managePages]" type="checkbox" @can('managePages') checked @endcan>
            Zarządzanie
        </label></li>

        <li class="separator">Administracja</li>
        <li><label class="checkbox">
            <input name="perms[manageUsers]" type="checkbox" @can('manageUsers') checked @endcan>
            Zarządzanie użytkownikami
        </label></li>
        <li><label class="checkbox">
            <input name="perms[manageStore]" type="checkbox" @can('manageStore') checked @endcan>
            Zarządzanie sklepem
        </label></li>
    </ol>

    @can('manageUsers')
        <br>
        <button class="button is-black">Zapisz</button>
    @endcan

</form>
@endsection
