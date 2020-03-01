@extends('admin.layout')

@section('title', $product->name ?? 'Nowy Produkt')

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
                    <input name="name" class="input @error('name') is-danger @enderror" required autocomplete="off" value="{{ old('name') ?? $product->name ?? '' }}">
                </div>
                @error('name')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="slug">Link</label>
                <div class="control">
                    <input name="slug" class="input @error('slug') is-danger @enderror" required autocomplete="off" value="{{ old('slug') ?? $product->slug ?? '' }}">
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
                            <option value="{{ $brand->id }}" {{ $brand->id == (old('brand_id') ?? $product->brand_id ?? '')  ? 'selected' : '' }}>
                                {{ $brand->name }}
                            </option>
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
                            <option value="{{ $category->id }}" {{ $category->id == (old('category_id') ?? $product->category_id ?? '') ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
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

            <div class="columns has-no-margin-bottom">
                <div class="column">
                    <div class="field">
                        <label class="label" for="price">Cena brutto</label>
                        <div class="control">
                            <input type="number" step="0.01" name="price" class="input @error('price') is-danger @enderror" required autocomplete="off" value="{{ old('price') ?? $product->price ?? '' }}">
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
                                <select name="tax_id">
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax['id'] }}"  {{ $tax['id'] == (old('tax_id') ?? $product->tax_id ?? '') ? 'selected' : '' }}>
                                        {{ $tax['name'] }}
                                    </option>
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
                    <textarea name="description" rows="7" class="textarea">{{ old('description') ?? $product->description ?? '' }}</textarea>
                </div>
                @error('description')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
                <small>MD supported <a target="_blank" href="https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet">[?]</a></small>
            </div>
        </div>
    </div>

    <div>
        <label class="label">Zdjęcia</label>
        <div id="tabs" class="gallery"></div>
        <div>
            <br><button class="button is-black">Zapisz</button>
        </div>
    </div>
</form>

<script src="/js/gallery.js"></script>
@endsection

{{-- @push('head')
    <script>
        window.oldPhotos = [
            @if (isset($product))
            @foreach ($product->gallery as $photo)
                {
                    url: "{{ $photo->url }}",
                    id: "{{ $photo->id }}"
                },
            @endforeach
            @endif
        ];
    </script>
@endpush --}}
