@extends('admin/layout')

@section('title', $page->name)

@section('buttons')
<a href="/admin/pages/{{ $page->slug }}/update" class="top-nav--button">
    <img class="icon" src="/img/icons/pencil.svg">
</a>
<button onclick="window.confirmModal(
        'Czy na pewno chcesz usunąć {{ $page->name }}?',
        '/admin/pages/{{ $page->slug }}/delete'
    )" class="top-nav--button">
    <img class="icon" src="/img/icons/trash.svg">
</button>
@endsection

@section('content')
<div class="content">{!! $page->content !!}</div>
@endsection
