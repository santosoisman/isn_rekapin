{{-- Chip bulan yang bisa digeser. Param: $months, $routeName, $extra (query tambahan) --}}
<div class="-mx-4 sm:mx-0 px-4 sm:px-0 flex gap-2 overflow-x-auto no-scrollbar py-1"
     x-data x-init="$nextTick(() => { const a = $el.querySelector('[data-active]'); if (a) $el.scrollLeft = a.offsetLeft - ($el.clientWidth - a.offsetWidth) / 2; })">
    @foreach($months as $m)
        <a href="{{ route($routeName, array_merge($extra ?? [], ['bulan' => $m['key']])) }}"
           @if($m['active']) data-active @endif
           class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium border {{ $m['active'] ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200' }}">
            {{ $m['label'] }}
        </a>
    @endforeach
</div>
