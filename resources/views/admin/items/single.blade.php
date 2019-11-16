@extends('admin/layout')

@section('title', $item->name)

@section('buttons')

@endsection

@section('content')
<div class="stats">
    <div class="stats__item">
        <img class="icon" src="/img/icons/code.svg">
        {{ $item->symbol }}
    </div>
    <div class="stats__item">
        <img class="icon" src="/img/icons/list.svg">
        @if ($item->category)
            {{ $item->category->name }}
        @else
            Brak kategorii
        @endif
    </div>
    <div class="stats__item">
        <img class="icon" src="/img/icons/chest.svg">
        {{ $item->qty }} szt.
    </div>
</div>

<div class="order">
    <div>
        <h3></h3>
        <div class="img">
            <img src="//source.unsplash.com/collection/1085173">
        </div>
    </div>
    <div>
        <h3 class="margin--left">Dokumenty magazynowe</h3>
        <div class="list">
            <li class="clickable marginles">
                <div>PZ1-19</div>
                <small>2019-11-15</small>
            </li>
        </div>
    </div>

</div>
@endsection

@section('scripts')

@endsection
