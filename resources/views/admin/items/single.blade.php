@extends('admin/layout')

@section('title', $item->name)

@section('buttons')
<button onclick="window.confirmModal(
        'Czy na pewno chcesz usunąć {{ $item->name }}?',
        '/admin/items/{{ $item->id }}/delete'
    )" class="top-nav--button">
    <img class="icon" src="/img/icons/trash.svg">
</button>
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
            @if ($item->photo)
                <img src="{{ $item->photo->url }}">
            @endif
        </div>
    </div>
    {{-- <div>
        <h3 class="margin--left">Dokumenty magazynowe</h3>
        <div class="list">
            <li class="clickable marginles">
                <div>PZ1-19</div>
                <small>2019-11-15</small>
            </li>
        </div>
    </div> --}}
</div>
@endsection

@section('scripts')

@endsection
