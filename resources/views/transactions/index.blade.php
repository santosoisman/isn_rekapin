@extends('layouts.app')
@section('title', 'Transaksi')
@section('content')
<div class="space-y-4">

    @include('partials.month-tabs', ['routeName' => 'transactions.index', 'extra' => ['jenis' => $jenis === 'semua' ? null : $jenis]])

    {{-- Ringkasan bulan + filter jenis --}}
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h2 class="text-sm font-semibold text-gray-500 mb-2">{{ $monthLabel }}</h2>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-xs text-gray-500">Pemasukan</p>
                <p class="font-bold text-emerald-600">Rp {{ number_format($monthIncome, 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500">Pengeluaran</p>
                <p class="font-bold text-red-600">Rp {{ number_format($monthExpense, 0, ',', '.') }}</p>
            </div>
        </div>
        @php
            $bulanParam = request()->query('bulan');
            $filters = ['semua' => 'Semua', 'keluar' => 'Pengeluaran', 'masuk' => 'Pemasukan'];
        @endphp
        <div class="grid grid-cols-3 bg-gray-100 rounded-lg p-1 text-sm font-medium mt-3">
            @foreach($filters as $key => $label)
                <a href="{{ route('transactions.index', array_filter(['bulan' => $bulanParam, 'jenis' => $key === 'semua' ? null : $key])) }}"
                   class="text-center py-1.5 rounded-md {{ $jenis === $key ? 'bg-white shadow text-indigo-600' : 'text-gray-500' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @if($jenis !== 'masuk')
        @include('partials.category-donut', ['breakdown' => $breakdown, 'monthLabel' => $monthLabel])
    @endif

    <script type="application/json" id="tx-items-by-category-json">{!! json_encode($itemsByCategory) !!}</script>

    {{-- Tab per hari: pilih tanggal, hanya transaksi hari itu yang tampil --}}
    <div x-data="{ day: '{{ $activeDay }}' }">
    @if($days->isNotEmpty())
        <div class="-mx-4 sm:mx-0 px-4 sm:px-0 flex gap-2 overflow-x-auto no-scrollbar py-1 mb-3"
             x-init="$nextTick(() => { const a = $el.querySelector('[data-active]'); if (a) $el.scrollLeft = a.offsetLeft - ($el.clientWidth - a.offsetWidth) / 2; })">
            @foreach($days as $key => $day)
                <button type="button" @click="day = '{{ $key }}'"
                        :data-active="day === '{{ $key }}' ? '' : null"
                        :class="day === '{{ $key }}' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200'"
                        class="shrink-0 px-3 py-1.5 rounded-xl border text-center leading-tight">
                    <span class="block text-[10px]" style="text-transform:uppercase;">{{ $day['date']->copy()->locale('id')->translatedFormat('D') }}</span>
                    <span class="block text-sm font-semibold">{{ $isAll ? $day['date']->copy()->locale('id')->translatedFormat('j M') : $day['date']->format('j') }}</span>
                </button>
            @endforeach
        </div>
        @if($isAll && ($hasNewer || $hasOlder))
            @php $pageQuery = ['bulan' => 'semua', 'jenis' => $jenis === 'semua' ? null : $jenis]; @endphp
            <div class="flex justify-between text-xs mb-3 px-1">
                @if($hasNewer)
                    <a href="{{ route('transactions.index', array_filter($pageQuery + ['hal' => $hal - 1 > 1 ? $hal - 1 : null])) }}" class="text-indigo-600 hover:underline">‹ Lebih baru</a>
                @else
                    <span></span>
                @endif
                @if($hasOlder)
                    <a href="{{ route('transactions.index', array_filter($pageQuery + ['hal' => $hal + 1])) }}" class="text-indigo-600 hover:underline">Lebih lama ›</a>
                @endif
            </div>
        @endif
    @endif

    @forelse($days as $key => $day)
        <div x-show="day === '{{ $key }}'" x-cloak>
            <div class="flex items-center justify-between px-1 mb-1.5">
                <p class="text-xs font-semibold text-gray-500">{{ $day['date']->copy()->locale('id')->translatedFormat('l, d M Y') }}</p>
                <p class="text-xs font-medium {{ $day['net'] < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                    {{ $day['net'] < 0 ? '-' : '+' }} Rp {{ number_format(abs($day['net']), 0, ',', '.') }}
                </p>
            </div>

            <div class="bg-white rounded-xl shadow-sm divide-y">
                @foreach($day['rows'] as $tx)
                    @if($tx->tx_type === 'expense')
                        <div data-category-id="{{ $tx->category_id }}" data-item-name="{{ $tx->item->name ?? '' }}"
                             x-data="{
                                editing: false,
                                categoryId: '',
                                itemsByCategory: {},
                                itemQuery: '', itemMenuOpen: false,
                                init() {
                                    this.categoryId = this.$el.dataset.categoryId;
                                    this.itemQuery = this.$el.dataset.itemName;
                                    this.itemsByCategory = JSON.parse(document.getElementById('tx-items-by-category-json').textContent || '{}');
                                },
                                get filteredItems() {
                                    const list = this.itemsByCategory[this.categoryId] || [];
                                    if (!this.itemQuery) return list;
                                    return list.filter(i => i.toLowerCase().includes(this.itemQuery.toLowerCase()));
                                },
                                pickItem(name) { this.itemQuery = name; this.itemMenuOpen = false; }
                             }" class="p-3">
                            <div x-show="!editing" class="flex items-start justify-between gap-3 text-sm">
                                <div class="min-w-0">
                                    <p class="font-medium truncate">{{ $tx->item->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-400">
                                        {{ $tx->category->name }}@if($tx->note) · {{ $tx->note }}@endif · {{ $tx->user->name }}
                                    </p>
                                    @if(!empty($tx->receipt['items']))
                                        <details class="mt-1">
                                            <summary class="text-xs text-indigo-600 cursor-pointer">🧾 {{ count($tx->receipt['items']) }} barang</summary>
                                            @include('partials.receipt-detail', ['receipt' => $tx->receipt, 'total' => $tx->amount])
                                        </details>
                                    @endif
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-red-600 font-semibold whitespace-nowrap">- Rp {{ number_format($tx->amount, 0, ',', '.') }}</p>
                                    @can('update', $tx)
                                        <div class="flex items-center justify-end gap-3 mt-1">
                                            <button @click="editing = true" class="text-xs text-indigo-600 hover:underline">Edit</button>
                                            <form method="POST" action="{{ route('expenses.destroy', $tx) }}" onsubmit="return confirm('Hapus transaksi ini?')">
                                                @csrf @method('DELETE')
                                                <button class="text-xs text-red-600 hover:underline">Hapus</button>
                                            </form>
                                        </div>
                                    @endcan
                                </div>
                            </div>

                            <form x-show="editing" x-cloak method="POST" action="{{ route('expenses.update', $tx) }}"
                                  class="grid grid-cols-2 gap-2">
                                @csrf @method('PUT')
                                <select name="category_id" x-model="categoryId" @change="itemQuery = ''" class="border rounded px-2 py-1.5 text-sm">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" @selected($cat->id === $tx->category_id)>{{ $cat->name }}</option>
                                    @endforeach
                                </select>

                                <div class="relative">
                                    <input type="text" name="item_name" x-model="itemQuery" required autocomplete="off"
                                           @focus="itemMenuOpen = true" @input="itemMenuOpen = true"
                                           placeholder="Item" class="border rounded px-2 py-1.5 text-sm w-full">
                                    <div x-show="itemMenuOpen" @click.outside="itemMenuOpen = false" x-cloak
                                         class="absolute z-10 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-40 overflow-auto">
                                        <template x-for="opt in filteredItems" :key="opt">
                                            <button type="button" @click="pickItem(opt)" x-text="opt"
                                                    class="block w-full text-left px-3 py-1.5 text-sm hover:bg-indigo-50"></button>
                                        </template>
                                        <p x-show="filteredItems.length === 0" class="px-3 py-1.5 text-xs text-gray-400">Item baru</p>
                                    </div>
                                </div>

                                <input type="number" step="0.01" name="amount" value="{{ $tx->amount }}" class="border rounded px-2 py-1.5 text-sm">
                                <input type="date" name="date" value="{{ $tx->date->format('Y-m-d') }}" class="border rounded px-2 py-1.5 text-sm">
                                <input type="text" name="note" value="{{ $tx->note }}" placeholder="Catatan" class="col-span-2 border rounded px-2 py-1.5 text-sm">
                                <div class="col-span-2 flex gap-2">
                                    <button class="text-xs bg-indigo-600 text-white px-3 py-1.5 rounded">Simpan</button>
                                    <button type="button" @click="editing = false" class="text-xs px-3 py-1.5 rounded border">Batal</button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div x-data="{ editing: false }" class="p-3">
                            <div x-show="!editing" class="flex items-start justify-between gap-3 text-sm">
                                <div class="min-w-0">
                                    <p class="font-medium">Pemasukan</p>
                                    <p class="text-xs text-gray-400">
                                        @if($tx->note){{ $tx->note }} · @endif{{ $tx->user->name }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-green-600 font-semibold whitespace-nowrap">+ Rp {{ number_format($tx->amount, 0, ',', '.') }}</p>
                                    @can('update', $tx)
                                        <div class="flex items-center justify-end gap-3 mt-1">
                                            <button @click="editing = true" class="text-xs text-indigo-600 hover:underline">Edit</button>
                                            <form method="POST" action="{{ route('incomes.destroy', $tx) }}" onsubmit="return confirm('Hapus transaksi ini?')">
                                                @csrf @method('DELETE')
                                                <button class="text-xs text-red-600 hover:underline">Hapus</button>
                                            </form>
                                        </div>
                                    @endcan
                                </div>
                            </div>

                            <form x-show="editing" x-cloak method="POST" action="{{ route('incomes.update', $tx) }}"
                                  class="grid grid-cols-2 gap-2">
                                @csrf @method('PUT')
                                <input type="number" step="0.01" name="amount" value="{{ $tx->amount }}" class="border rounded px-2 py-1.5 text-sm">
                                <input type="date" name="date" value="{{ $tx->date->format('Y-m-d') }}" class="border rounded px-2 py-1.5 text-sm">
                                <input type="text" name="note" value="{{ $tx->note }}" placeholder="Catatan" class="col-span-2 border rounded px-2 py-1.5 text-sm">
                                <div class="col-span-2 flex gap-2">
                                    <button class="text-xs bg-indigo-600 text-white px-3 py-1.5 rounded">Simpan</button>
                                    <button type="button" @click="editing = false" class="text-xs px-3 py-1.5 rounded border">Batal</button>
                                </div>
                            </form>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm p-6 text-center text-sm text-gray-400">
            Belum ada transaksi{{ $isAll ? '' : ' di '.$monthLabel }}.
        </div>
    @endforelse
    </div>

</div>
@endsection
