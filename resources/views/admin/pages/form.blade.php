@extends('admin/layout')

@section('title', $page->name ?? 'Nowa Strona')

@section('buttons')

@endsection

@section('content')
<form method="post" enctype="multipart/form-data">
    @csrf
    <div class="field">
        <label class="label" for="name">Nazwa</label>
        <div class="control">
            <input name="name" class="input @error('name') is-danger @enderror" required autocomplete="off" value="{{ old('name', $page->name ?? '') }}">
        </div>
        @error('name')
            <p class="help is-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <label class="label" for="slug">Link</label>
        <div class="control">
            <input name="slug" class="input @error('slug') is-danger @enderror" required autocomplete="off" value="{{ old('slug', $page->slug ?? '') }}">
        </div>
        @error('slug')
            <p class="help is-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="checkbox">
        <label for="public">
            <input type="hidden" name="public" value="0">
            <input name="public" id="public" type="checkbox" class="checkbox" value="1" {{ old('public') ? 'checked' : $page->public ? 'checked' : '' }}>
            Widoczność na stronie
        </label>
        @error('public')
            <p class="help is-danger">{{ $message }}</p>
        @enderror
    </div><br><br>

    <div class="field">
        <label class="label" for="content">Treść</label>
        <div class="control">
            <textarea name="content" rows="14" class="textarea">{{ old('content', $page->content ?? '') }}</textarea>
        </div>
        @error('content')
            <p class="help is-danger">{{ $message }}</p>
        @enderror
        <small>MD supported <a target="_blank" href="https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet">[?]</a></small>
    </div>
    <button class="button is-black">Zapisz</button>
</form>

<script src="/js/gallery.js"></script>
@endsection
