@extends('admin/layout')

@section('title', 'Dostęp do panelu')

@section('buttons')
<a href="/admin/settings/accounts/add" class="top-nav--button">
  <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol class="list">
  @foreach($accounts as $user)
    <li class="center">
      <img class="avatar" src="{{ $user->avatar() }}">
      <span class="margin__left">
        <div>{{ $user['name'] }}</div>
        <small>{{ $user['email'] }}</small>
      </span>
    </li>
  @endforeach
</ol>
@endsection

@section('scripts')
  
@endsection