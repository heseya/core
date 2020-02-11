@extends('admin/layout')

@section('title', 'Dostęp do panelu')

@section('buttons')
<a href="/admin/settings/users/create" class="top-nav--button">
    <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol class="list">
    @foreach($users as $user)
        <a href="/admin/settings/users/{{ $user->id }}">
            <li class="center clickable">
                <img class="avatar" src="{{ $user->avatar() }}">
                <span class="margin__left">
                    <div>
                        {{ $user->name }}
                        {{ $user->id == Auth::user()->id ? '(Ty)' : '' }}
                    </div>
                    <small>{{ $user->email }}</small>
                </span>
            </li>
        </a>
    @endforeach
</ol>
@endsection
