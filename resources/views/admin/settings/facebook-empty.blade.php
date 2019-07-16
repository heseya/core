@extends('admin/layout')

@section('title', 'Facebook')

@section('buttons')
  
@endsection

@section('content')
<ol class="list list--settings">
  <li>Nie jesteś połączony.</li>
  <a href="/admin/settings/facebook/login">
    <li class="clickable">
      <img class="icon" src="/img/icons/facebook.svg">
      Połącz z kontem Facebook
    </li>
  </a>
</ol>
@endsection

@section('scripts')
  
@endsection