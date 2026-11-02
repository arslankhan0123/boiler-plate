{{-- App-wide pagination (overrides pagination::bootstrap-5). --}}
@if ($paginator->hasPages())
<nav class="app-pager" role="navigation" aria-label="Pagination">
    <div class="app-pager__info">
        Showing <b>{{ $paginator->firstItem() }}</b>–<b>{{ $paginator->lastItem() }}</b>
        @if (method_exists($paginator, 'total')) of <b>{{ $paginator->total() }}</b>@endif
    </div>
    <ul class="app-pager__list">
        @if ($paginator->onFirstPage())
            <li><span class="app-pager__btn is-disabled" aria-hidden="true"><i class="bi bi-chevron-left"></i></span></li>
        @else
            <li><a class="app-pager__btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous"><i class="bi bi-chevron-left"></i></a></li>
        @endif

        @isset($elements)
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="app-pager__btn is-dots">…</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="app-pager__btn is-active" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a class="app-pager__btn" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
        @endisset

        @if ($paginator->hasMorePages())
            <li><a class="app-pager__btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next"><i class="bi bi-chevron-right"></i></a></li>
        @else
            <li><span class="app-pager__btn is-disabled" aria-hidden="true"><i class="bi bi-chevron-right"></i></span></li>
        @endif
    </ul>
</nav>
@once
<style>
    .app-pager { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; width: 100%; }
    .app-pager__info { font-size: .85rem; color: var(--app-text-muted, #64748b); }
    .app-pager__info b { color: var(--app-text, #0f172a); font-weight: 600; }
    .app-pager__list { display: flex; gap: 6px; list-style: none; margin: 0; padding: 0; }
    .app-pager__btn { min-width: 36px; height: 36px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px;
        border: 1px solid var(--app-border, #e2e8f0); background: #fff; color: var(--app-text, #0f172a); font-size: .85rem; font-weight: 600; text-decoration: none; transition: .15s; }
    a.app-pager__btn:hover { border-color: #c7d2fe; background: #eef2ff; color: var(--app-primary, #4f46e5); }
    .app-pager__btn.is-active { background: var(--app-primary, #4f46e5); border-color: var(--app-primary, #4f46e5); color: #fff; box-shadow: 0 4px 12px rgba(79,70,229,.25); }
    .app-pager__btn.is-disabled { opacity: .45; cursor: not-allowed; }
    .app-pager__btn.is-dots { border-color: transparent; background: transparent; }
</style>
@endonce
@endif
