<nav class="pagination">

    @if ($paginator->onFirstPage())
        <a class="pagination-previous" disabled>Poprzednia</a>
    @else
        <a href="{{ $paginator->previousPageUrl() }}"  class="pagination-previous">Poprzednia</a>
    @endif

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}"  class="pagination-next">Następna</a>
    @else
        <a class="pagination-next" disabled>Następna</a>
    @endif

    <ul class="pagination-list">
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <li><span class="pagination-ellipsis">&hellip;</span></li>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li><a class="pagination-link is-current" href="{{ $url }}">{{ $page }}</a></li>
                    @else
                        <li><a class="pagination-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach
    </ul>
</nav>
