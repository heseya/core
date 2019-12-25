@extends('admin/layout')

@section('title', 'Nowy Produkt')

@section('buttons')

@endsection

@section('content')
<form method="post" enctype="multipart/form-data">
    @csrf

    <div class="columns">
        <div class="column">
            <div class="field">
                <label class="label" for="name">Nazwa</label>
                <div class="control">
                    <input name="name" class="input @error('name') is-danger @enderror" required autocomplete="off" value="{{ old('name') }}">
                </div>
                @error('name')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="slug">Link</label>
                <div class="control">
                    <input name="slug" pattern="[a-z0-9]+(?:-[a-z0-9]+)" class="input @error('slug') is-danger @enderror" required autocomplete="off" value="{{ old('slug') }}">
                </div>
                @error('slug')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="brand_id">Marka</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="brand_id">
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                        </select>
                    </div>
                </div>
                @error('brand_id')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="category_id">Kategoria</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="category_id">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                        </select>
                    </div>
                </div>
                @error('category_id')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

        </div>
        <div class="column">

            <div class="columns">
                <div class="column">
                    <div class="field">
                        <label class="label" for="price">Cena brutto</label>
                        <div class="control">
                            <input type="number" step="0.01" name="price" class="input @error('price') is-danger @enderror" required autocomplete="off" value="{{ old('price') }}">
                        </div>
                        @error('price')
                            <p class="help is-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="column">
                    <div class="field">
                        <label class="label" for="vat">VAT</label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="vat">
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax['id'] }}">{{ $tax['name'] }}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>
                        @error('vat')
                            <p class="help is-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="field">
                <label class="label" for="description">Opis</label>
                <div class="control">
                    <textarea name="description" rows="7" class="textarea">{{ old('description') }}</textarea>
                </div>
                @error('description')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div>
        <label class="label">Zdjęcia</label>
        <div id="tabs" class="gallery"></div>
        <div>
            <br><button class="button is-black">Dodaj</button>
        </div>
    </div>

    <div class="">

    </div>
</form>
@endsection
