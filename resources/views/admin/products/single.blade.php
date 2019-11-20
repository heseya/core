@extends('admin/layout')

@section('title', $product->name)

@section('buttons')
<button onclick="window.confirmModal(
        'Czy na pewno chcesz usunąć {{ $product->name }}?',
        '/admin/products/{{ $product->id }}/delete'
    )" class="top-nav--button">
    <img class="icon" src="/img/icons/trash.svg">
</button>
@endsection

@section('content')
<div class="product-photos">
    <div class="gallery">
        @foreach ($product->photos as $photo)
            <div class="gallery__img" style="background-color: {{ $product->color }}">
                <img src="{{ $photo->url }}">
            </div>
        @endforeach
    </div>
</div>

<div class="grid grid--2">
    <div>
        <h2>{{ \App\Money::PLN($product->price) }}</h2>
        <p>{{ $product->description }}</p>
    </div>

    <div class="cart">
        @foreach ($product->shema as $schema)
            <h3>{{ $schema->name }} <small style="color: #aaa">{{ $schema->required ? 'wymagany' : '' }}</small></h3>
            <div class="list">
                @foreach ($schema->items as $item)
                <a href="/admin/items/{{ $item->id }}" class="cart__item">
                    <div class="cart__img">
                    @if ($item->photo)
                        <img src="{{ $item->photo->url }}">
                    @endif
                    </div>
                    <div class="cart__details">
                        <div>{{ $item->name }} <small>x {{ $item->qty }}</small></div>
                        <small>
                            {{ $item->symbol }}
                            @if ($item->pivot->extraPrice > 0)
                            | + {{ \App\Money::PLN($item->pivot->extraPrice) }}
                            @endif
                        </small>
                    </div>
                </a>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
@endsection

@section('scripts')

@endsection
