{{-- YPA table pagination footer: "Showing X–Y of Z" summary + compact page links.
     Registered as the default paginator view in AppServiceProvider, so every
     $paginator->links() call renders this. --}}
@if ($paginator->total() > 0)
    <nav class="ypa-pagination" aria-label="Pagination">
        <div class="ypa-pagination-summary">
            Showing <strong>{{ number_format($paginator->firstItem()) }}</strong>&ndash;<strong>{{ number_format($paginator->lastItem()) }}</strong>
            of <strong>{{ number_format($paginator->total()) }}</strong>
        </div>

        @if ($paginator->hasPages())
            <ul class="pagination pagination-sm mb-0">
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-hidden="true"><i class="fas fa-chevron-left"></i></span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous"><i class="fas fa-chevron-left"></i></a></li>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next"><i class="fas fa-chevron-right"></i></a></li>
                @else
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link" aria-hidden="true"><i class="fas fa-chevron-right"></i></span></li>
                @endif
            </ul>
        @endif
    </nav>
@endif
