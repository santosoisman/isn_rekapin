@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="space-y-5">

    {{-- Total keseluruhan (semua bulan) --}}
    <div class="rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-500 text-white p-5 shadow">
        <p class="text-xs text-indigo-100">Saldo (semua bulan)</p>
        <p class="text-3xl font-bold mt-1 {{ $saldo < 0 ? 'text-red-200' : '' }}">
            Rp {{ number_format($saldo, 0, ',', '.') }}
        </p>
        <div class="grid grid-cols-2 gap-3 mt-4 pt-4 border-t border-white/20">
            <div>
                <p class="text-xs text-indigo-100">Total Pemasukan</p>
                <p class="font-semibold">Rp {{ number_format($totalIncome, 0, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-xs text-indigo-100">Total Pengeluaran</p>
                <p class="font-semibold">Rp {{ number_format($totalExpense, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Tab bulan --}}
    @include('partials.month-tabs', ['routeName' => 'dashboard', 'extra' => []])

    {{-- Ringkasan bulan terpilih --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-500 mb-2">Ringkasan {{ $monthLabel }}</h2>
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-emerald-400">
                <p class="text-xs text-gray-500">Pemasukan</p>
                <p class="text-base sm:text-lg font-bold text-emerald-600 break-words">Rp {{ number_format($monthIncome, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-red-400">
                <p class="text-xs text-gray-500">Pengeluaran</p>
                <p class="text-base sm:text-lg font-bold text-red-600 break-words">Rp {{ number_format($monthExpense, 0, ',', '.') }}</p>
            </div>
            <div class="col-span-2 bg-white px-4 py-3 rounded-xl shadow-sm flex items-center justify-between">
                <p class="text-xs text-gray-500">Selisih bulan ini</p>
                @php $diff = $monthIncome - $monthExpense; @endphp
                <p class="font-bold {{ $diff < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                    {{ $diff < 0 ? '-' : '+' }} Rp {{ number_format(abs($diff), 0, ',', '.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Pengeluaran per kategori (bulan terpilih), tap untuk lihat item --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 items-start">
        @foreach($categories as $cat)
            <div class="bg-white rounded-xl shadow-sm" x-data="{ open: false }">
                <button type="button" @click="open = !open" class="w-full text-left p-4">
                    <div class="flex items-center justify-between gap-1">
                        <p class="text-xs text-gray-500">{{ $cat->name }}</p>
                        <svg class="w-3 h-3 shrink-0 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <p class="font-semibold break-words">Rp {{ number_format($categoryTotals[$cat->id] ?? 0, 0, ',', '.') }}</p>
                </button>
                <div x-show="open" x-cloak class="px-4 pb-3 border-t pt-2 space-y-1">
                    @forelse($itemTotalsByCategory[$cat->id] ?? [] as $item)
                        <div class="flex justify-between gap-2 text-xs text-gray-600">
                            <span>{{ $item['name'] }}</span>
                            <span class="shrink-0">Rp {{ number_format($item['total'], 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">Belum ada transaksi bulan ini.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- Transaksi bulan terpilih --}}
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold">Transaksi {{ $monthLabel }}</h2>
            <a href="{{ route('transactions.index', array_filter(['bulan' => request('bulan')])) }}" class="text-sm text-indigo-600 hover:underline">Lihat semua / edit</a>
        </div>
        <ul class="divide-y">
            @forelse($recent as $tx)
                <li class="py-2 flex justify-between gap-3 text-sm">
                    <div class="min-w-0">
                        <span class="text-gray-400 text-xs block">{{ $tx->date->format('d M Y') }}</span>
                        {{ $tx->label }}{{ $tx->note ? ' ('.$tx->note.')' : '' }}
                        @if(!empty($tx->receipt['items']))
                            <details class="mt-1">
                                <summary class="text-xs text-indigo-600 cursor-pointer">🧾 {{ count($tx->receipt['items']) }} barang</summary>
                                @include('partials.receipt-detail', ['receipt' => $tx->receipt, 'total' => $tx->amount])
                            </details>
                        @endif
                    </div>
                    <span class="font-medium shrink-0 {{ $tx->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                        {{ $tx->type === 'income' ? '+' : '-' }} Rp {{ number_format($tx->amount, 0, ',', '.') }}
                    </span>
                </li>
            @empty
                <li class="py-2 text-sm text-gray-400">Belum ada transaksi di bulan ini.</li>
            @endforelse
        </ul>
        @if($monthTxCount > $recent->count())
            <p class="text-xs text-gray-400 mt-2">Menampilkan {{ $recent->count() }} dari {{ $monthTxCount }} transaksi.</p>
        @endif
    </div>
</div>
@endsection
