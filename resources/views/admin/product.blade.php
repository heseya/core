@extends('admin/layout')

@section('title', $product->name)

@section('buttons')

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

<div class="margin--30 margin--top">
    <h2>{{ $product->price }} zł</h2>

    <p>{{ $product->description }}</p>
</div>
@endsection

@section('scripts')

@endsection
