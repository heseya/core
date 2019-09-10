@extends('admin/layout')

@section('title', $product->name)

@section('buttons')

@endsection

@section('content')
<div class="stats">
  <div class="stats__item">
    <img class="icon" src="/img/icons/bookmark.svg">203
  </div>
  <div class="stats__item">
    <img class="icon" src="/img/icons/user.svg">22
  </div>
  <div class="stats__item">
    <img class="icon" src="/img/icons/sad.svg">0
  </div>
</div>
@endsection

@section('scripts')

@endsection
