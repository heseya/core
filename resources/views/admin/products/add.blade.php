@extends('admin/layout')

@section('title', 'Nowy Produkt')

@section('buttons')

@endsection

@section('content')
<form method="post" enctype="multipart/form-data">
    @csrf

    <div class="grid grid--2">
        <div>
            <div class="input sto">
                <label for="name">Nazwa</label>
                <input type="text" name="name" required>
            </div>
            <div class="input sto">
                <label for="slug">Link</label>
                <input type="text" name="slug" required>
            </div>
            <div class="input sto">
                <label for="brand">Marka</label>
                <select type="text" name="brand_id" required>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="input sto">
                <label for="category">Kategoria</label>
                <select type="text" name="category_id" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <div class="grid grid--2 grid--no-margin">
                <div class="input sto" style="margin-bottom: 0">
                    <label for="price">Cena brutto (PLN)</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
                <div class="input sto" style="margin-bottom: 0">
                    <label for="category">VAT</label>
                    <select type="text" name="vat" required>
                        @foreach($taxes as $tax)
                            <option value="{{ $tax['id'] }}">{{ $tax['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="input sto">
                <label for="description">Opis</label>
                <textarea name="desctiprion" rows="10"></textarea>
            </div>
        </div>
    </div>

    <div class="product-add">
        <p>Zdjęcia</p>
        <div id="tabs" class="gallery"></div>
        <div>
            <br><button class="button sto sto-mobile">Dodaj</button>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script src="/js/gallery.js"></script>
@endsection
