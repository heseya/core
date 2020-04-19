@extends('admin.layout')

@section('title', 'Magazyn')

@section('buttons')
<a href="{{ route('items.create') }}" class="top-nav--button">
    <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol class="list list--chat">
    @foreach ($items as $item)
        <a href="{{ route('items.view', $item) }}">
            <li class="clickable">
                <div class="avatar">
                    @if ($item->photo)
                        <img src="{{ $item->photo->url }}">
                    @endif
                </div>
                <div>
                    <div>
                        {{ $item->name }}
                        <small>x {{ $item->qty }}</small>
                    </div>
                    <small>
                        {{ $item->symbol }}
                    </small>
                </div>
            </li>
        </a>
    @endforeach
</ol>

<br>
{{ $items->links() }}
@endsection
