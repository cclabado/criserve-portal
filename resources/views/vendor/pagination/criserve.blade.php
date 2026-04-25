@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-slate-500">
            Showing
            <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>
            to
            <span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
            of
            <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
            results
        </div>

        <div class="flex items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-3 text-sm font-semibold text-slate-400">
                    Prev
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   rel="prev"
                   class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50">
                    Prev
                </a>
            @endif

            <div class="flex items-center gap-2">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-transparent px-2 text-sm text-slate-400">
                            {{ $element }}
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl bg-[#0B3C5D] px-3 text-sm font-bold text-white shadow-sm">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}"
                                   class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   rel="next"
                   class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50">
                    Next
                </a>
            @else
                <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-3 text-sm font-semibold text-slate-400">
                    Next
                </span>
            @endif
        </div>
    </nav>
@endif
