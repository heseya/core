@extends('admin/layout')

@section('title', $user->name)

@section('buttons')

@endsection

@section('content')
<form action="/admin/settings/users/{{ $user->id }}/rbac" method="post">
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

        <li><label class="checkbox">
            <input name="perms[viewProducts]" type="checkbox" @can('viewProducts') checked @endcan>
            Przeglądanie produktów
        </label></li>
        <li><label class="checkbox">
            <input name="perms[menageProducts]" type="checkbox" @can('manageProducts') checked @endcan>
            Przeglądanie produktów
        </label></li>
        <li><label class="checkbox">
            <input name="perms[menageUsers]" type="checkbox" @can('manageUsers') checked @endcan>
            Zarządzanie użytkownikami
        </label></li>
    </ol>

    @can('manageUsers')
        <button class="button is-black">Zapisz</button>
    @endcan

</form>
@endsection
