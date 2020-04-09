@extends('admin.layout')

@section('title', $item->name)

@section('buttons')
<a href="{{ route('items.update', $item->id) }}" class="top-nav--button">
    <img class="icon" src="/img/icons/pencil.svg">
</a>
<button onclick="window.confirmModal(
        'Czy na pewno chcesz usunąć {{ $item->name }}?',
        '{{ route('items.delete', $item->id) }}/delete'
    )" class="top-nav--button">
    <img class="icon" src="/img/icons/trash.svg">
</button>
@endsection

@section('content')
<div class="stats">
    <div class="stats__item">
        <img class="icon" src="/img/icons/code.svg">
        {{ $item->symbol ?? 'BRAK' }}
    </div>
    <div class="stats__item">
        <img class="icon" src="/img/icons/chest.svg">
        {{ $item->qty }} szt.
    </div>
    @if ($item->category)
        <div class="stats__item">
            <img class="icon" src="/img/icons/list.svg">
            {{ $item->category->name }}
        </div>
    @endif
</div>

<div class="columns is-multiline">
    <div class="column is-half">
        <div class="img">
            @if ($item->photo)
                <img src="{{ $item->photo->url }}">
            @endif
        </div>
    </div>
    <div class="column is-half">
        <h3 class="margin--left">Powiązane produkty</h3>
        <div class="list">
            @foreach ($item->schemaItems as $schemaItem)
            <a href="{{ route('products.view', $schemaItem->schema->product->slug) }}" class="cart__item">
                <div class="cart__img">
                @isset ($schemaItem->schema->product->gallery[0])
                    <img src="{{ $schemaItem->schema->product->gallery[0]->url }}">
                @endisset
                </div>
                <div class="cart__details">
                    <div>{{ $schemaItem->schema->product->name }}</div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
