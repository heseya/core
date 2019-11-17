@extends('admin/layout')

@section('title', 'Nowy Towar')

@section('buttons')

@endsection

@section('content')
<form method="post" enctype="multipart/form-data">
    @csrf

    <div class="grid grid--2">
        <div>
            <div class="input sto">
                <label for="name">Nazwa</label>
                <input type="text" name="name" required autocomplete="off">
            </div>
            <div class="input sto">
                <label for="symbol">Symbol</label>
                <input type="text" name="symbol" required autocomplete="off">
            </div>
            <div class="input sto">
                <label for="category">Kategoria</label>
                <select type="text" name="category_id">
                    <option value="NULL">- brak -</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <p>Zdjęcie</p>
            <div id="tabs" class="gallery"></div>
        </div>
    </div>

    <div class="product-add">
        <div>
            <br><button class="button sto sto-mobile">Dodaj</button>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script src="/js/gallery.js"></script>
@endsection
