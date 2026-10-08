{{--
    Donut pengeluaran per Jenis Pengeluaran + perbandingan dengan bulan lalu.
    Butuh: $breakdown (App\Support\CategoryBreakdown::make), $monthLabel
    Sengaja pakai inline style supaya tidak perlu `npm run build`.
--}}
@php
    $slices = $breakdown['slices'];
    $rp = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');

    // Badge perubahan: pengeluaran naik = merah, turun = hijau
    $badge = function (?float $ch, float $total) {
        if ($ch === null) {
            return $total > 0 ? ['baru', 'text-gray-500'] : ['', ''];
        }
        if ($ch > 0) return ['▲ ' . abs($ch) . '%', 'text-red-600'];
        if ($ch < 0) return ['▼ ' . abs($ch) . '%', 'text-emerald-600'];
        return ['sama', 'text-gray-500'];
    };

    $isAll = ! empty($breakdown['all']);
    $gap = count($slices) > 1 ? 0.8 : 0;
    $cum = 0;
@endphp

<div class="bg-white rounded-xl shadow-sm p-4" x-data="{ sel: null }">
    <div class="flex items-center justify-between mb-1">
        <h2 class="font-semibold">Pengeluaran per Kategori</h2>
        <span class="text-xs text-gray-500">{{ $monthLabel }}</span>
    </div>

    @if(count($slices) === 0)
        <p class="text-sm text-gray-400 py-6 text-center">Belum ada pengeluaran di {{ $monthLabel }}.</p>
    @else
        @if($isAll)
            {{-- Mode Semua: statistik per bulan, bukan perbandingan --}}
            <p class="text-xs text-gray-500 mb-3">
                Rata-rata {{ $rp($breakdown['avgMonth']) }}/bulan ({{ $breakdown['monthCount'] }} bulan)
                @if($breakdown['monthCount'] > 1)
                    · paling boros <span class="font-semibold text-red-600">{{ $breakdown['maxMonth']['label'] }}</span> ({{ $rp($breakdown['maxMonth']['total']) }})
                    · paling hemat <span class="font-semibold text-emerald-600">{{ $breakdown['minMonth']['label'] }}</span> ({{ $rp($breakdown['minMonth']['total']) }})
                @endif
            </p>
        @else
            {{-- Perbandingan total vs bulan lalu --}}
            <p class="text-xs text-gray-500 mb-3">
                @if($breakdown['change'] === null)
                    Belum ada data {{ $breakdown['prevLabel'] }} untuk dibandingkan.
                @elseif($breakdown['change'] == 0)
                    Sama dengan {{ $breakdown['prevLabel'] }} ({{ $rp($breakdown['prevTotal']) }}).
                @else
                    <span class="font-semibold {{ $breakdown['change'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                        {{ $breakdown['change'] > 0 ? 'Naik' : 'Turun' }} {{ abs($breakdown['change']) }}%
                    </span>
                    dari {{ $breakdown['prevLabel'] }} ({{ $rp($breakdown['prevTotal']) }})
                @endif
            </p>
        @endif

        {{-- Donut --}}
        <div style="position:relative;width:11rem;max-width:100%;margin:0 auto;">
            <svg viewBox="0 0 42 42" style="display:block;width:100%;height:auto;" role="img"
                 aria-label="Diagram donut pengeluaran per kategori {{ $monthLabel }}">
                <g transform="rotate(-90 21 21)">
                    @foreach($slices as $i => $s)
                        @php
                            $len = max($s['pct'] - $gap, 0.1);
                            $off = -$cum;
                            $cum += $s['pct'];
                        @endphp
                        <circle cx="21" cy="21" r="15.9155" fill="none"
                                stroke="{{ $s['color'] }}" stroke-width="5.5"
                                stroke-dasharray="{{ $len }} {{ 100 - $len }}" stroke-dashoffset="{{ $off }}"
                                style="cursor:pointer;transition:opacity .15s;"
                                :style="sel !== null && sel !== {{ $i }} ? 'opacity:.25' : ''"
                                @click="sel = sel === {{ $i }} ? null : {{ $i }}">
                            <title>{{ $s['name'] }}: {{ $rp($s['total']) }} ({{ $s['pct'] }}%)</title>
                        </circle>
                    @endforeach
                </g>
            </svg>

            {{-- Isi tengah donut --}}
            <div style="position:absolute;top:0;left:0;right:0;bottom:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;pointer-events:none;padding:0 2.2rem;">
                <div x-show="sel === null">
                    <p class="text-xs text-gray-500">Total</p>
                    <p class="text-sm font-bold break-words">{{ $rp($breakdown['total']) }}</p>
                </div>
                @foreach($slices as $i => $s)
                    <div x-show="sel === {{ $i }}" x-cloak>
                        <p class="text-xs text-gray-500">{{ $s['name'] }}</p>
                        <p class="text-sm font-bold break-words">{{ $rp($s['total']) }}</p>
                        <p class="text-xs text-gray-500">{{ $s['pct'] }}%</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Legenda: tap baris untuk lihat rincian item --}}
        <div class="mt-4 divide-y">
            @foreach($slices as $i => $s)
                @php [$btxt, $bcls] = $isAll ? ['', ''] : $badge($s['change'], $s['total']); @endphp
                <div>
                    <button type="button" class="w-full text-left py-2 flex items-center gap-2"
                            @click="sel = sel === {{ $i }} ? null : {{ $i }}">
                        <span style="width:.75rem;height:.75rem;border-radius:9999px;flex-shrink:0;background:{{ $s['color'] }};"></span>
                        <span class="text-sm flex-1 min-w-0 break-words">{{ $s['name'] }}</span>
                        <span class="text-right shrink-0">
                            <span class="text-sm font-semibold block">{{ $rp($s['total']) }}</span>
                            <span class="text-xs text-gray-500">{{ $s['pct'] }}%
                                @if($btxt !== '')<span class="{{ $bcls }}">· {{ $btxt }}</span>@endif
                            </span>
                        </span>
                        <svg class="w-3 h-3 shrink-0 text-gray-400 transition-transform" :class="sel === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="sel === {{ $i }}" x-cloak class="pb-2 space-y-1" style="padding-left:1.25rem;">
                        @foreach($s['items'] as $it)
                            <div class="flex justify-between gap-2 text-xs text-gray-600">
                                <span class="break-words">{{ $it['name'] }}</span>
                                <span class="shrink-0">{{ $rp($it['total']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @unless($isAll)
            <p class="text-[11px] text-gray-400 mt-2">Perbandingan dihitung terhadap {{ $breakdown['prevLabel'] }}.</p>
        @endunless
    @endif
</div>
