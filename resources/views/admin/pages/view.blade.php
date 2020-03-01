@extends('admin.layout')

@section('title', $page->name)

@section('buttons')
<a href="{{ route('pages.update', $page->slug) }}" class="top-nav--button">
    <img class="icon" src="/img/icons/pencil.svg">
</a>
<button onclick="window.confirmModal(
        'Czy na pewno chcesz usunąć {{ $page->name }}?',
        '{{ route('pages.delete', $page->slug) }}'
    )" class="top-nav--button">
    <img class="icon" src="/img/icons/trash.svg">
</button>
@endsection

@section('content')
<div class="content">{!! $page->parsed_content !!}</div>
@endsection
