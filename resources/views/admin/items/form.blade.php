@extends('admin/layout')

@section('title', $item->name ?? 'Nowy Towar')

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
                    <input name="name" class="input @error('name') is-danger @enderror" required autocomplete="off" value="{{ old('name') ?? $item->name ?? '' }}">
                </div>
                @error('name')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="symbol">Symbol</label>
                <div class="control">
                    <input name="symbol" class="input @error('symbol') is-danger @enderror" required autocomplete="off" value="{{ old('symbol') ?? $item->symbol ?? '' }}">
                </div>
                @error('symbol')
                    <p class="help is-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="column">
            <label class="label" for="name">Zdjęcie</label>
            <div id="tabs" class="gallery"></div>
        </div>
    </div>
    <button class="button is-black">Zapisz</button>
</form>

<script src="/js/gallery.js"></script>
@endsection
