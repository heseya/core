@extends('admin/layout')

@section('title', 'Konwersacje')

@section('buttons')
  
@endsection

@section('content')
<ol class="list list--chat">
  @foreach($chats as $chat)
    <a href="/admin/chat/{{ $chat['id'] }}">
      <li class="clickable">
        <div class="avatar">
          <img src="/img/avatar.jpg">
        </div>
        <div>
          <div class="{{ $chat['unread_count'] > 0 ? 'unread' : '' }}">{{ $chat['participants'][0]['name'] }}</div>
          <small>{{ $chat['snippet'] }}</small>
        </div>
      </li>
    </a>
  @endforeach
</ol>
@endsection

@section('scripts')
  
@endsection
