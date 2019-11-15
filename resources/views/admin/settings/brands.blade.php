@extends('admin/layout')

@section('title', 'Marki')

@section('buttons')
<a href="/admin/settings/brands/add" class="top-nav--button">
    <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol class="list">
    @foreach($brands as $brand)
        <li class="center clickable">
            <span class="margin__left">
                <div>{{ $brand->name }}</div>
                <small>/{{ $brand->slug }}</small>
            </span>
        </li>
    @endforeach
</ol>
@endsection

@section('scripts')

@endsection
