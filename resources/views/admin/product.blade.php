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
        <div class="gallery__img">
            <img src="https://source.unsplash.com/collection/1085173/500x500">
        </div>
        <div class="gallery__img">
            <img src="https://source.unsplash.com/collection/1085173/250x250?1">
        </div>
        <div class="gallery__img">
            <img src="https://source.unsplash.com/collection/1085173/250x250?2">
        </div>
    </div>
</div>

<div class="product-description">
    {{ $product->description }}
</div>
@endsection

@section('scripts')

@endsection
