@extends('admin.layout')

@section('title', $category->name ?? 'Nowa kategoria')

@section('buttons')

@endsection

@section('content')
<form method="post">
    @csrf

    @error('has-products')
        <p class="help is-danger">{{ $message }}</p>
    @enderror

    <div class="field">
        <label class="label" for="name">Nazwa</label>
        <div class="control">
            <input name="name" class="input @error('name') is-danger @enderror" required autocomplete="off" value="{{ old('name') ?? $category->name ?? '' }}">
        </div>
        @error('name')
            <p class="help is-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <label class="label" for="slug">Link</label>
        <div class="control">
            <input name="slug" class="input @error('slug') is-danger @enderror" required autocomplete="off" value="{{ old('slug') ?? $category->slug ?? '' }}">
        </div>
        @error('slug')
            <p class="help is-danger">{{ $message }}</p>
        @enderror
    </div>


    <div class="field">
        <div class="control">
            <label class="checkbox">
                <input name="public" type="checkbox" {{ (old('public') ?? $category->public ?? false) ? 'checked' : '' }}>
                Widoczność w sklepie
            </label>
            @error('public')
                <p class="help is-danger">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <button class="button is-black">Zapisz</button>
    @isset($category)
        <a class="button is-black" href="{{ route('categories.delete', $category) }}">Usuń</a>
    @endisset
</form>
@endsection
