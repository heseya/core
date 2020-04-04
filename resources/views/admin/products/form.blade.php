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

            <div class="field">
                <label class="label" for="type">Typ</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select id="digital" name="digital" oninput="changeProductType()">
                            <option value="0" {{ (old('digital') ?? $product->digital ?? 0) == 0 ? 'selected' : '' }}>Fizyczny</option>
                            <option value="1" {{ (old('digital') ?? $product->digital ?? 0) == 1 ? 'selected' : '' }}>Cyfrowy</option>
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
                                    <option value="{{ $tax['id'] }}" {{ $tax['id'] == (old('tax_id') ?? $product->tax_id ?? '') ? 'selected' : '' }}>
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
        <div class="columns has-no-margin-bottom" id="schema-button">
            @if(!(old('digital') ?? $product->digital ?? false))
                <div class="column">
                    <label class="label">Schematy</label>
                </div>
                <div class="column">
                    <div id="schema-add" class="top-nav--button" onclick="addSchema()">
                        <img class="icon" src="/img/icons/plus.svg">
                    </div>
                </div>
            @endif
        </div>
        <div id="schemas">
            <input type="hidden" id="schema-count" name="schemas" value='0'>
            @isset($schemas)
                @foreach($schemas as $schema)
                    @if($schema->name != NULL)
                        <div id="schema-old-{{ $schema->id }}">
                            <div class="columns has-no-margin-bottom">
                                <div class="column">
                                    <div class="field">
                                        <label class="label" for="schema-old-{{ $schema->id }}-name">Nazwa</label>
                                        <div class="control">
                                            <input name="schema-old-{{ $schema->id }}-name" class="input @error('schema-old-' . $schema->id . '-name') is-danger @enderror" 
                                            required autocomplete="off" value="{{ old('schema-old-' . $schema->id . '-name') ?? $schema['name'] }}">
                                        </div>
                                        @error('schema-old-' . $schema->id . 'name')
                                            <p class="help is-danger">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="column">
                                    <div class="field">
                                        <label class="label" for="schema-old-{{ $schema->id }}-type">Typ</label>
                                        <div class="control">
                                            <div class="select is-fullwidth">
                                                <select id="schema-old-{{ $schema->id }}-type" name="schema-old-{{ $schema->id }}-type"
                                                oninput="changeSchemaType({{ $schema->id }}, true)">
                                                    <option value="0" {{ (old('schema-old-' . $schema->id . '-type') ?? $schema['type']) == 0
                                                    ? 'selected' : '' }}>Produkty</option>
                                                    <option value="1" {{ (old('schema-old-' . $schema->id . '-type') ?? $schema['type']) == 1
                                                    ? 'selected' : '' }}>Pole tekstowe</option>
                                                </select>
                                            </div>
                                        </div>
                                        @error('schema-old-' . $schema->id . '-type')
                                            <p class="help is-danger">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="column">
                                    <div class="field">
                                        <label class="label" for="schema-old-{{ $schema->id }}-required">Wymagany</label>
                                        <div class="control">
                                            <div class="select is-fullwidth">
                                                <select name="schema-old-{{ $schema->id }}-required">
                                                    <option value="0" {{ (old('schema-old-' . $schema->id . '-required') ?? $schema->required) == 0
                                                    ? 'selected' : '' }}>Opcjonalny</option>
                                                    <option value="1" {{ (old('schema-old-' . $schema->id . '-required') ?? $schema->required) == 1
                                                    ? 'selected' : '' }}>Wymagany</option>
                                                </select>
                                            </div>
                                        </div>
                                        @error('schema-old-' . $schema->id . '-required')
                                            <p class="help is-danger">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="column">
                                    <div class="top-nav--button" onclick="deleteSchema({{ $schema->id }}, true)">
                                        <img class="icon" src="/img/icons/trash.svg">
                                    </div>
                                </div>
                            </div>
                            <div id="schema-old-{{ $schema->id }}-items">
                                <input type="hidden" id="schema-old-{{ $schema->id }}-item-count" name="schema-old-{{ $schema->id }}-items" value="0">
                                @foreach($schema->schemaItems as $schemaItem)
                                    <div id="schema-old-{{ $schema->id }}-item-{{ $schemaItem->id }}" class="columns has-no-margin-bottom">
                                        <div class="column">
                                            <div class="field">
                                                <label class="label" for="schema-old-{{ $schema->id }}-item-{{ $schemaItem->id }}-id">Produkt</label>
                                                <div class="control">
                                                    <div class="select is-fullwidth">
                                                        <select name="schema-old-{{ $schema->id }}-item-{{ $schemaItem->id }}-id">
                                                            @foreach($items as $item_db)
                                                                <option value="{{ $item_db->id }}" {{ (old('schema-old-' . $schema->id . '-item-' . $schemaItem->id . '-id')
                                                                ?? $schemaItem->item_id) == $item_db->id ? 'selected' : '' }}>{{ $item_db->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                @error('schema-old-' . $schema->id . '-item-' . $schemaItem->id . '-id')
                                                    <p class="help is-danger">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="column">
                                            <div class="field">
                                                <label class="label" for="schema-old-{{ $schema->id }}-item-{{ $schemaItem->id }}-price">Dodatkowa cena</label>
                                                <div class="control">
                                                    <input type="number" name="schema-old-{{ $schema->id }}-item-{{ $schemaItem->id }}-price"
                                                    class="input @error('item-price') is-danger @enderror" required autocomplete="off" step="0.01"
                                                    value="{{ old('schema-old-' . $schema->id . '-item-' . $schemaItem->id . '-price') ?? $schemaItem->extra_price ?? 0 }}">
                                                </div>
                                                @error('schema-old-' . $schema->id . '-item-' . $schemaItem->id . '-price')
                                                    <p class="help is-danger">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="column">
                                            <div class="top-nav--button" onclick="deleteItem({{ $schema->id }}, {{ $schemaItem->id }}, true)">
                                                <img class="icon" src="/img/icons/trash.svg">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div id="schema-old-{{ $schema->id }}-button" class="columns has-no-margin-bottom">
                                @if($schema->type == 0)
                                    <div class="column">
                                        <div class="button is-black" onclick="addItem({{ $schema->id }}, true)">Dodaj przedmiot</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            @endisset
        </div>
        <div style="display: none">
            <select id="items-template">
                @foreach($items as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            </select>
        </div>
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
