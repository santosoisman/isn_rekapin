@extends('layouts.app')
@section('title', 'History')
@section('content')
<div x-data="calendarApp('{{ $month }}')" class="bg-white rounded-lg shadow p-6">

    <div class="flex items-center justify-between mb-4">
        <button @click="changeMonth(-1)" class="px-3 py-1 border rounded">&lt;</button>
        <h2 class="font-semibold" x-text="monthLabel"></h2>
        <button @click="changeMonth(1)" class="px-3 py-1 border rounded">&gt;</button>
    </div>

    <div class="grid grid-cols-7 gap-2 text-center text-xs text-gray-400 mb-2">
        <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
    </div>

    <div class="grid grid-cols-7 gap-2">
        <template x-for="blank in leadingBlanks" :key="'b'+blank">
            <div></div>
        </template>
        <template x-for="day in daysInMonth" :key="day">
            <button @click="selectDay(day)"
                    class="aspect-square rounded border text-sm flex flex-col items-center justify-center hover:bg-indigo-50"
                    :class="selectedDay === day ? 'ring-2 ring-indigo-500' : ''">
                <span x-text="day"></span>
                <span x-show="totals[dayKey(day)]" class="text-[10px] text-red-500" x-text="totals[dayKey(day)] ? 'Rp' + formatShort(totals[dayKey(day)]) : ''"></span>
            </button>
        </template>
    </div>

    <div x-show="detail" x-cloak class="mt-6 border-t pt-4">
        <h3 class="font-semibold mb-2" x-text="'Detail ' + selectedDateLabel"></h3>
        <template x-if="detail">
            <div class="space-y-2 text-sm">
                <template x-for="(group, catName) in detail.groupedExpenses" :key="catName">
                    <div class="border rounded-lg overflow-hidden">
                        <button type="button" @click="toggleCategory(catName)"
                                class="w-full flex items-center justify-between px-3 py-2 hover:bg-gray-50">
                            <span x-text="catName"></span>
                            <span class="flex items-center gap-2">
                                <span class="text-red-600" x-text="'- Rp ' + formatFull(group.total)"></span>
                                <svg class="w-3 h-3 text-gray-400 transition-transform" :class="categoryOpen[catName] ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </span>
                        </button>
                        <div x-show="categoryOpen[catName]" x-cloak class="px-3 pb-2 pt-1 border-t space-y-1 bg-gray-50">
                            <template x-for="item in group.items" :key="item.id">
                                <div>
                                    <div class="flex justify-between text-xs text-gray-600">
                                        <span x-text="item.name + (item.note ? ' — ' + item.note : '')"></span>
                                        <span x-text="'Rp ' + formatFull(item.amount)"></span>
                                    </div>
                                    <template x-if="item.receipt && item.receipt.items && item.receipt.items.length">
                                        <details class="mt-0.5">
                                            <summary class="text-[11px] text-indigo-600 cursor-pointer" x-text="'🧾 ' + item.receipt.items.length + ' barang'"></summary>
                                            <div class="mt-1 pl-2 space-y-0.5 text-[11px] text-gray-500">
                                                <template x-for="(r, idx) in item.receipt.items" :key="idx">
                                                    <div class="flex justify-between gap-2">
                                                        <span x-text="(r.qty > 1 ? r.qty + '× ' : '') + r.name"></span>
                                                        <span x-text="r.price != null ? formatFull(r.price) : ''"></span>
                                                    </div>
                                                </template>
                                                <p class="text-[10px] text-gray-400">Hasil scan, tidak ikut perhitungan.</p>
                                            </div>
                                        </details>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-for="inc in detail.incomes" :key="inc.id">
                    <div class="flex justify-between px-1">
                        <span x-text="'Pemasukan' + (inc.note ? ' — ' + inc.note : '')"></span>
                        <span class="text-green-600">+ Rp <span x-text="formatFull(inc.amount)"></span></span>
                    </div>
                </template>

                <template x-if="Object.keys(detail.groupedExpenses).length === 0 && detail.incomes.length === 0">
                    <p class="text-gray-400">Tidak ada transaksi di tanggal ini.</p>
                </template>
            </div>
        </template>
    </div>
</div>

<script>
function calendarApp(initialMonth) {
    return {
        month: initialMonth,
        totals: @json($expenses->map(fn($e) => (float) $e->total)),
        selectedDay: null,
        detail: null,
        categoryOpen: {},

        get monthLabel() {
            const [y, m] = this.month.split('-');
            return new Date(y, m - 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
        },
        get daysInMonth() {
            const [y, m] = this.month.split('-');
            return new Date(y, m, 0).getDate();
        },
        get leadingBlanks() {
            const [y, m] = this.month.split('-');
            return new Date(y, m - 1, 1).getDay();
        },
        get selectedDateLabel() {
            return this.selectedDay ? this.dayKey(this.selectedDay) : '';
        },
        dayKey(day) {
            const [y, m] = this.month.split('-');
            return `${y}-${m.padStart(2,'0')}-${String(day).padStart(2,'0')}`;
        },
        changeMonth(delta) {
            const [y, m] = this.month.split('-').map(Number);
            const d = new Date(y, m - 1 + delta, 1);
            window.location = `{{ route('history.calendar') }}?month=${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
        },
        async selectDay(day) {
            this.selectedDay = day;
            const res = await fetch(`/history/${this.dayKey(day)}`);
            const data = await res.json();

            // Kelompokkan pengeluaran per kategori, biar bisa di-expand per kategori
            const groups = {};
            (data.expenses || []).forEach(e => {
                const catName = e.category ? e.category.name : '-';
                if (!groups[catName]) groups[catName] = { total: 0, items: [] };
                groups[catName].total += parseFloat(e.amount);
                groups[catName].items.push({
                    id: e.id,
                    name: e.item ? e.item.name : '-',
                    amount: parseFloat(e.amount),
                    note: e.note,
                    receipt: e.receipt || null,
                });
            });
            data.groupedExpenses = groups;

            this.detail = data;
            this.categoryOpen = {};
        },
        toggleCategory(name) {
            this.categoryOpen[name] = !this.categoryOpen[name];
        },
        formatShort(v) {
            return Math.round(v / 1000) + 'rb';
        },
        formatFull(v) {
            return Number(v).toLocaleString('id-ID');
        },
    }
}
</script>
@endsection
