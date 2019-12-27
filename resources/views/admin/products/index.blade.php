@extends('admin/layout')

@section('title', 'Asortyment')

@section('buttons')
<a href="/admin/products/create" class="top-nav--button">
    <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<div class="products-list">
    @foreach ($products as $product)
        <a href="/admin/products/{{ $product->id }}" class="product">
            <div class="product__img">
                @if (isset($product->gallery[0]))
                    <img src="{{ $product->gallery[0]->url }}" />
                @endif
            </div>
            <div class="flex">
                <div class="name">
                {{ $product->name }}<br/>
                <small>{{ \App\Money::PLN($product->price) }}</small>
                </div>
            </div>
        </a>
    @endforeach
</div>

{{ $products->links() }}
@endsection
