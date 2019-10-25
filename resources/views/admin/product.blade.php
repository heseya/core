@extends('admin/layout')

@section('title', $product->name)

@section('buttons')

@endsection

@section('content')
<div class="stats">
    <div class="stats__item">
        <img class="icon" src="/img/icons/money.svg">250 PLN
    </div>
</div>

<div class="product-photos">
    <div class="gallery">
        <div class="gallery__img" style="background-color: #{{ $product->color }}">
            <img src="/img/snake.png">
        </div>
        <div class="gallery__img" style="background-color: #{{ $product->color }}">
            <img src="/img/snake1.png">
        </div>
        <div class="gallery__img" style="background-color: #{{ $product->color }}">
            <img src="/img/snake3.jpg">
        </div>
    </div>
</div>

<div class="product-description">
    {{ $product->description }}
</div>
@endsection

@section('scripts')

@endsection
