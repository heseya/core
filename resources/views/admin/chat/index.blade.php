@extends('admin/layout')

@section('title', 'Konwersacje')

@section('buttons')

@endsection

@section('content')
<ol id="chats" class="list list--chat">
    @foreach ($chats as $chat)
        <a href="/admin/chat/{{ $chat->id }}">
            <li class="clickable">
                <div class="avatar">
                    <img src="{{ $chat->avatar() }}">
                </div>
                <div>
                    <div class="{{ $chat->unread ? 'unread' : '' }}">
                        {{ $chat->client->name }}
                    </div>
                    <small>{!! $chat->snippet() !!}</small>
                </div>
            </li>
        </a>
    @endforeach
</ol>

<br>
{{ $chats->links() }}
@endsection
