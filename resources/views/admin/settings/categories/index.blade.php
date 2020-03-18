@extends('admin.layout')

@section('title', 'Kategorie')

@section('buttons')
<a href="{{ route('categories.create') }}" class="top-nav--button">
    <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol class="list">
    @foreach($categories as $category)
        <a href="{{ route('categories.update', $category) }}">
            <li class="center clickable">
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
        </a>
    @endforeach
</ol>
@endsection
