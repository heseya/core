@extends('admin.layout')

@section('title', 'Strony')

@section('buttons')
<a href="{{ route('pages.create') }}" class="top-nav--button">
    <img class="icon" src="/img/icons/plus.svg">
</a>
@endsection

@section('content')
<ol class="list">
    @foreach($pages as $page)
        <a href="{{ route('pages.view', $page->slug) }}">
            <li class="center clickable">
                <span class="margin__left">
                    <div>
                        @if (!$page->public)
                            <img class="small-img" src="/img/icons/locker.svg">
                        @endif
                        {{ $page->name }}
                    </div>
                    <small>/{{ $page->slug }}</small>
                </span>
            </li>
        </a>
    @endforeach
</ol>

<br>
{{ $pages->links() }}
@endsection
