@extends('admin/layout')

@section('title', 'Dostęp do panelu')

@section('buttons')
<a href="/admin/settings/accounts/create" class="top-nav--button">
  <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol class="list">
  @foreach($accounts as $account)
    <li class="center">
      <img class="avatar" src="{{ $account->avatar() }}">
      <span class="margin__left">
        <div>{{ $account['name'] }}</div>
        <small>{{ $account['email'] }}</small>
      </span>
    </li>
  @endforeach
</ol>
@endsection
