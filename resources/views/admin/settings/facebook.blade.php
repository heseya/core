@extends('admin/layout')

@section('title', 'Facebook')

@section('buttons')
  
@endsection

@section('content')
<ol class="list">
  <li class="separator">Podłączone konto</li>
  <li class="center">
    <img class="avatar" src="{{ $user_fb['picture']['url'] }}">
    <span class="margin__left">
      <div>{{ $user_fb['name'] }}</div>
      <small>{{ $user_fb['id'] }}</small>
    </span>
  </li>

  <li class="separator">Aktywna strona</li>
  <li class="center">
    <img class="avatar" src="{{ $page['picture']['url'] }}">
    <span class="margin__left">
      <div>{{ $page['name'] }}</div>
      <small>{{ $page['id'] }}</small>
    </span>
  </li>
</ol>

<ol class="list list--settings">
  <a href="/admin/settings/facebook/pages">
    <li class="clickable">
      <img class="icon" src="/img/icons/switch.svg"> Przełącz stronę
    </li>
  </a>
  <a href="/admin/settings/facebook/unlink">
    <li class="clickable">
      <img class="icon" src="/img/icons/logout.svg"> Odłącz konto
    </li>
  </a>
</ol>
@endsection

@section('scripts')
  
@endsection