{{-- Rincian barang dari scan struk (hanya keterangan). Param: $receipt (array), $total (nominal transaksi) --}}
@php
    $rcItems = $receipt['items'] ?? [];
    $rcSum = collect($rcItems)->sum(fn ($i) => $i['price'] ?? 0);
    $rcAllPriced = collect($rcItems)->every(fn ($i) => isset($i['price']));
    $rcDiff = (float) $total - $rcSum;
@endphp
<div class="mt-1 rounded-lg bg-gray-50 border px-3 py-2 text-xs text-gray-600 space-y-0.5">
    @if(!empty($receipt['merchant']))
        <p class="font-medium">{{ $receipt['merchant'] }}</p>
    @endif
    @foreach($rcItems as $i)
        <div class="flex justify-between gap-3">
            <span>{{ ($i['qty'] ?? 1) > 1 ? $i['qty'] . '× ' : '' }}{{ $i['name'] }}</span>
            <span class="shrink-0">{{ isset($i['price']) ? number_format($i['price'], 0, ',', '.') : '' }}</span>
        </div>
    @endforeach
    @if($rcAllPriced && abs($rcDiff) >= 1)
        <div class="flex justify-between gap-3 border-t pt-1 mt-1 text-gray-400">
            <span>Selisih (diskon/pajak)</span>
            <span class="shrink-0">{{ $rcDiff < 0 ? '-' : '+' }} {{ number_format(abs($rcDiff), 0, ',', '.') }}</span>
        </div>
    @endif
    <p class="text-[10px] text-gray-400 pt-1">Hasil scan, bisa meleset. Tidak ikut perhitungan.</p>
</div>
