@extends('admin/layout')

@section('title', 'Powiadomienia')

@section('buttons')
  
@endsection

@section('content')
<ol class="list list--settings">
  <li>
    <input name="new" id="new" class="switch" type="checkbox">
    <label for="new">Nowe zamówienie</label>
  </li>
</ol>
<ol class="list list--settings">
  <li>
    <input name="new" id="new" class="switch" type="checkbox">
    <label for="new">Nowa wiadomość</label>
  </li>
</ol>
@endsection

@section('scripts')
  
@endsection