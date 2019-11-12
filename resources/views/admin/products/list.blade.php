@extends('admin/layout')

@section('title', 'Asortyment')

@section('buttons')
<a href="/admin/products/add" class="top-nav--button">
  <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<div class="products-categories"></div>

<div id="products" class="products-list">
    @foreach ($products as $product)
        <a href="/admin/products/{{ $product->id }}" class="product">
            <div class="product__img" style="background-color: {{ $product->color }}">
                @if (isset($product->photos[0]))
                    <img src="{{ $product->photos[0]->url }}" />
                @endif
            </div>
            <div class="flex">
                <div class="name">
                {{ $product->name }}<br/>
                <small>{{ $product->price }}</small>
                </div>
            </div>
        </a>
    @endforeach
</div>
@endsection

@section('scripts')

@endsection
