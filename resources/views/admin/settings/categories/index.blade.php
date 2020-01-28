@extends('admin/layout')

@section('title', 'Kategorie')

@section('buttons')
<a href="/admin/settings/categories/create" class="top-nav--button">
    <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol class="list">
    @foreach($categories as $category)
        <li class="center">
            <span class="margin__left">
                <div>
                    @if (!$category->public)
                        <img class="small-img" src="/img/icons/locker.svg">
                    @endif
                    {{ $category->name }}
                </div>
                <small>/{{ $category->slug }}</small>
            </span>
        </li>
    @endforeach
</ol>
@endsection
