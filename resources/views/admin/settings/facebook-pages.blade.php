@extends('admin/layout')

@section('title', 'Wybierz stronę')

@section('buttons')
  
@endsection

@section('content')
<ol class="list">
  @foreach($pages as $page)
    <a href="/admin/settings/facebook/set-page/{{ $page['access_token'] }}">
      <li class="center clickable">
        <img class="round" src="{{ $page['picture']['url'] }}">
        <span class="margin__left">
          <div>{{ $page['name'] }}</div>
          <small>{{ $page['id'] }}</small>
        </span>
      </li>
    </a>
  @endforeach
</ol>
@endsection

@section('scripts')
  
@endsection