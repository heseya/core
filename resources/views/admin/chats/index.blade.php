@extends('admin.layout')

@section('title', 'Konwersacje')

@section('buttons')

@endsection

@section('content')
<ol id="chats" class="list list--chat">
    @foreach ($chats as $chat)
        <a href="{{ route('chats.view', $chat->id) }}">
            <li class="clickable">
                <div class="avatar">
                    <img src="{{ $chat->avatar() }}">
                </div>
                <div>
                    <div class="{{ $chat->unread ? 'unread' : '' }}">
                        {{ $chat->client->name ?? $chat->external_id }}
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
