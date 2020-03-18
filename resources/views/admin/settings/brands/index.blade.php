@extends('admin.layout')

@section('title', 'Marki')

@section('buttons')
<a href="{{ route('brands.create') }}" class="top-nav--button">
    <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol class="list">
    @foreach($brands as $brand)
        <a href="{{ route('brands.update', $brand) }}">
            <li class="center clickable">
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
        </a>
    @endforeach
</ol>
@endsection
