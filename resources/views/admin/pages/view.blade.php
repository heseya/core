@extends('admin/layout')

@section('title', $page->name)

@section('buttons')
<a href="/admin/pages/{{ $page->slug }}/update?lang={{ $selectLang }}" class="top-nav--button">
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
<div class="tabs">
    <ul>
        @foreach ($page->getTranslations('content') as $lang => $translation)
            <li {{ $selectLang == $lang ? 'class=is-active' : '' }}>
                <a href="/admin/pages/{{ $page->slug }}?lang={{ $lang }}">{{ $lang }}</a>
            </li>
        @endforeach
    </ul>
</div>

<div class="content">{!! $page->parsed_content !!}</div>
@endsection
