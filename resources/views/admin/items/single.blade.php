@extends('admin/layout')

@section('title', $item->name)

@section('buttons')
<button onclick="window.confirmModal(
        'Czy na pewno chcesz usunąć {{ $item->name }}?',
        '/admin/items/{{ $item->id }}/delete'
    )" class="top-nav--button">
    <img class="icon" src="/img/icons/trash.svg">
</button>
@endsection

@section('content')
<div class="stats">
    <div class="stats__item">
        <img class="icon" src="/img/icons/code.svg">
        {{ $item->symbol }}
    </div>
    <div class="stats__item">
        <img class="icon" src="/img/icons/chest.svg">
        {{ $item->qty }} szt.
    </div>
</div>

<div class="grid grid--2">
    <div>
        <h3></h3>
        <div class="img">
            @if ($item->photo)
                <img src="{{ $item->photo->url }}">
            @endif
        </div>
    </div>
    <div>
        <h3 class="margin--left">Powiązane produkty</h3>
        <div class="list">
            @foreach ($item->schemas as $schema)
            <a href="/admin/products/{{ $schema->product->slug }}" class="cart__item">
                <div class="cart__img">
                @if ($schema->product->photos[0])
                    <img src="{{ $schema->product->photos[0]->url }}">
                @endif
                </div>
                <div class="cart__details">
                    <div>{{ $schema->product->name }}</div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('scripts')

@endsection
