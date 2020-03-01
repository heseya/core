@extends('admin/layout')

@section('title', $chat->client['name'])

@section('buttons')

@endsection

@section('content')
<div class="chat">
    @foreach ($chat->messages as $message)
    <div class="message message--{{ empty($message->user_id) ? 'to' : 'from' }}">
        <div class="bubble">
            {{ $message->content }}
        </div>

        <div class="info">
            @if (!empty($message->user_id))
                {{ $message->user->name }},
            @endif
            {{ $message->created_at }}
        </div>
    </div>
    @endforeach
</div>

@if (count($chat->messages) < 1)
    <div class="has-text-centered">
        <small>Brak wiadomości</small>
    </div>
@endif

<form class="response" method="post">
    @csrf
    <div class="field has-addons">
        <div class="control is-expanded">
            <textarea name="message" rows="1" class="input @error('message') is-danger @enderror" placeholder="Aa">{{ old('message') }}</textarea>
        </div>
        <div class="control">
            <button type="submit" class="button is-black">Wyślij</button>
        </div>
    </div>
</form>
@endsection
