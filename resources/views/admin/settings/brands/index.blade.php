@extends('admin/layout')

@section('title', 'Marki')

@section('buttons')
<a href="/admin/settings/brands/create" class="top-nav--button">
    <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol class="list">
    @foreach($brands as $brand)
        <li class="center">
            <span class="margin__left">
                <div>
                    @if (!$brand->public)
                        <img class="small-img" src="/img/icons/locker.svg">
                    @endif
                    {{ $brand->name }}
                </div>
                <small>/{{ $brand->slug }}</small>
            </span>
        </li>
    @endforeach
</ol>
@endsection