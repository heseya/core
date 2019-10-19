@extends('admin/layout')

@section('title', 'Kategorie')

@section('buttons')
<a href="/admin/settings/categories/add" class="top-nav--button">
    <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol class="list">
    @foreach($categories as $category)
        <li class="center clickable">
            <span class="margin__left">
                <div>{{ $category['name'] }}</div>
                <small>/{{ $category['link'] }}</small>
            </span>
        </li>
    @endforeach
</ol>
@endsection

@section('scripts')

@endsection
