@extends('admin/layout')

@section('title', 'Nowy Towar')

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
                <label class="label" for="symbol">Symbol</label>
                <div class="control">
                    <input name="symbol" class="input @error('symbol') is-danger @enderror" required autocomplete="off" value="{{ old('symbol') }}">
                </div>
                @error('symbol')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="category_id">Kategoria</label>
                <div class="control">
                    <div class="select is-fullwidth">
                        <select name="category_id">
                            <option value="null">- brak -</option>
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
            <label class="label" for="name">Zdjęcie</label>
            <div id="tabs" class="gallery"></div>
        </div>
    </div>
    <button class="button is-black">Dodaj</button>
</form>
@endsection
