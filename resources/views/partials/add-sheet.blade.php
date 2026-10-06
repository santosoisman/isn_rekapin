{{-- Form tambah transaksi: bottom sheet di HP, modal di layar lebar. Dibuka lewat event 'open-add'. --}}
<div x-data="{ open: false, tab: 'expense' }"
     @open-add.window="open = true; tab = ($event.detail && $event.detail.tab) ? $event.detail.tab : tab"
     @keydown.escape.window="open = false"
     x-effect="document.body.classList.toggle('overflow-hidden', open)">

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="fixed inset-0 z-40 flex items-end sm:items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="open = false"></div>

        <div class="relative w-full sm:max-w-md bg-white rounded-t-2xl sm:rounded-2xl shadow-xl max-h-[92vh] overflow-y-auto"
             style="padding-bottom: env(safe-area-inset-bottom)">

            <div class="sticky top-0 bg-white z-10 px-4 pt-3 pb-2 border-b">
                <div class="mx-auto w-10 h-1 rounded-full bg-gray-300 mb-3 sm:hidden"></div>
                <div class="flex items-center justify-between mb-3">
                    <div class="min-w-0">
                        <h2 class="font-semibold">Tambah Transaksi</h2>
                        <p class="text-xs text-gray-400 truncate">ke buku: 📒 {{ $currentBook->name }}</p>
                    </div>
                    <button type="button" @click="open = false" class="p-1 text-gray-400 hover:text-gray-600" aria-label="Tutup">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="grid grid-cols-2 bg-gray-100 rounded-lg p-1 text-sm font-medium">
                    <button type="button" @click="tab = 'expense'" :class="tab === 'expense' ? 'bg-white shadow text-red-600' : 'text-gray-500'" class="py-1.5 rounded-md">Pengeluaran</button>
                    <button type="button" @click="tab = 'income'" :class="tab === 'income' ? 'bg-white shadow text-emerald-600' : 'text-gray-500'" class="py-1.5 rounded-md">Pemasukan</button>
                </div>
            </div>

            {{-- PEMASUKAN --}}
            <div x-show="tab === 'income'" class="p-4">
                <form method="POST" action="{{ route('incomes.store') }}" class="space-y-3" x-data="{ display: '' }">
                    @csrf
                    <input type="hidden" name="book_id" value="{{ $currentBook->id }}">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Jumlah</label>
                        <input type="text" inputmode="numeric" x-model="display"
                               @input="
                                   let raw = $event.target.value.replace(/[^0-9]/g, '');
                                   display = raw ? 'Rp ' + Number(raw).toLocaleString('id-ID') : '';
                                   $refs.incAmount.value = raw;
                               "
                               placeholder="Rp 0" class="w-full border rounded-lg px-3 py-2.5 text-lg">
                        <input type="hidden" name="amount" x-ref="incAmount">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Tanggal</label>
                        <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Catatan (opsional)</label>
                        <input type="text" name="note" placeholder="misal: Gaji bulanan" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <button class="w-full bg-emerald-600 text-white py-2.5 rounded-lg font-medium">Simpan Pemasukan</button>
                </form>
            </div>

            {{-- PENGELUARAN --}}
            <div x-show="tab === 'expense'" class="p-4"
                 data-default-category="{{ $sheetCategories->first()->id ?? '' }}"
                 data-ocr-url="{{ route('ocr.receipt') }}"
                 x-data="{
                    scanning: false, ocrText: '', ocrError: '', ocrInfo: '', menuOpen: false,
                    receiptItems: [], receiptMerchant: '', saveReceipt: true, itemAuto: false,
                    categoryId: '',
                    itemsByCategory: {},
                    itemQuery: '', itemMenuOpen: false,
                    amountDisplay: '',
                    init() {
                        this.categoryId = this.$el.dataset.defaultCategory;
                        this.itemsByCategory = JSON.parse(document.getElementById('items-by-category-json').textContent || '{}');
                        try {
                            const last = localStorage.getItem('rekapin_last_category');
                            if (last && Object.prototype.hasOwnProperty.call(this.itemsByCategory, last)) this.categoryId = last;
                        } catch (e) {}
                    },
                    categoryChanged() {
                        try { localStorage.setItem('rekapin_last_category', this.categoryId); } catch (e) {}
                        // Item hasil isian otomatis (mis. 'Belanja Indomaret') tidak ikut terhapus saat ganti jenis
                        if (!this.itemAuto) this.itemQuery = '';
                    },
                    get receiptPayload() {
                        if (!this.saveReceipt || !this.receiptItems.length) return '';
                        return JSON.stringify({ merchant: this.receiptMerchant, items: this.receiptItems });
                    },
                    titleCase(str) {
                        return str.toLowerCase().replace(/(^|\s)\S/g, c => c.toUpperCase());
                    },
                    get filteredItems() {
                        const list = this.itemsByCategory[this.categoryId] || [];
                        if (!this.itemQuery) return list;
                        return list.filter(i => i.toLowerCase().includes(this.itemQuery.toLowerCase()));
                    },
                    pickItem(name) {
                        this.itemQuery = name;
                        this.itemMenuOpen = false;
                    },
                    formatAmount(event) {
                        let raw = event.target.value.replace(/[^0-9]/g, '');
                        this.amountDisplay = raw ? 'Rp ' + Number(raw).toLocaleString('id-ID') : '';
                        this.$refs.expAmount.value = raw;
                    },
                    // Kecilkan foto dulu (maks 1600px, JPEG < ~900KB): foto kamera HP bisa 3-8MB,
                    // sedangkan nginx (default 1MB), PHP (default 2MB) dan OCR.space gratis (1MB) membatasi ukuran.
                    async compress(file) {
                        try {
                            const bmp = await createImageBitmap(file);
                            const scale = Math.min(1, 1600 / Math.max(bmp.width, bmp.height));
                            const c = document.createElement('canvas');
                            c.width = Math.round(bmp.width * scale);
                            c.height = Math.round(bmp.height * scale);
                            c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
                            let q = 0.85;
                            let blob = await new Promise(r => c.toBlob(r, 'image/jpeg', q));
                            while (blob && blob.size > 900 * 1024 && q > 0.4) {
                                q -= 0.1;
                                blob = await new Promise(r => c.toBlob(r, 'image/jpeg', q));
                            }
                            return blob ? new File([blob], 'struk.jpg', { type: 'image/jpeg' }) : file;
                        } catch (e) {
                            return file;
                        }
                    },
                    async scan(file) {
                        this.menuOpen = false;
                        this.ocrError = '';
                        this.ocrInfo = '';
                        this.ocrText = '';
                        this.receiptItems = [];
                        this.receiptMerchant = '';
                        this.scanning = true;
                        try {
                            const small = await this.compress(file);
                            const fd = new FormData();
                            fd.append('image', small);
                            const csrfToken = document.querySelector('meta[name=csrf-token]').content;
                            const r = await fetch('/ocr/receipt', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                                body: fd,
                            });
                            let data = null;
                            try { data = await r.json(); } catch (e) {}
                            if (!data) {
                                this.ocrError = r.status === 413
                                    ? 'Foto ditolak server (413: terlalu besar). Naikkan client_max_body_size di nginx.'
                                    : 'Server membalas error ' + r.status + '. Coba lagi atau isi manual.';
                            } else if (data.success) {
                                const found = [];
                                if (data.amount) {
                                    this.$refs.expAmount.value = data.amount;
                                    this.amountDisplay = 'Rp ' + Number(data.amount).toLocaleString('id-ID');
                                    found.push('jumlah ' + this.amountDisplay);
                                }
                                if (data.date) {
                                    this.$refs.expDate.value = data.date;
                                    found.push('tanggal ' + data.date);
                                }
                                if (data.merchant) {
                                    found.push('toko ' + data.merchant);
                                    if (!this.$refs.expNote.value) this.$refs.expNote.value = data.merchant;
                                }
                                this.ocrText = data.raw_text;
                                this.receiptItems = Array.isArray(data.items) ? data.items : [];
                                this.receiptMerchant = data.merchant || '';
                                if (this.receiptItems.length && !this.itemQuery) {
                                    this.itemQuery = data.merchant ? 'Belanja ' + this.titleCase(data.merchant) : 'Belanja';
                                    this.itemAuto = true;
                                }
                                this.ocrInfo = found.length
                                    ? 'Terbaca: ' + found.join(', ') + '. Cek lagi sebelum simpan.'
                                    : 'Struk terbaca tapi jumlah & tanggal tidak ketemu. Isi manual ya — teks hasil scan ada di bawah.';
                            } else {
                                this.ocrError = data.message
                                    || (data.errors ? Object.values(data.errors)[0][0] : 'Gagal membaca struk.');
                            }
                        } catch (e) {
                            this.ocrError = 'Tidak bisa terhubung ke server. Cek koneksi internet.';
                        }
                        this.scanning = false;
                    }
                 }">

                <script type="application/json" id="items-by-category-json">{!! json_encode($sheetItemsByCategory) !!}</script>

                <div class="relative inline-block mb-3">
                    <button type="button" @click="menuOpen = !menuOpen"
                            class="inline-flex items-center gap-2 text-sm text-indigo-600 border border-indigo-200 rounded-lg px-3 py-2 hover:bg-indigo-50">
                        <span x-show="!scanning">📷 Scan Struk (isi jumlah &amp; tanggal otomatis)</span>
                        <span x-show="scanning" x-cloak>⏳ Membaca struk...</span>
                    </button>

                    <div x-show="menuOpen" @click.outside="menuOpen = false" x-cloak
                         class="absolute left-0 mt-2 w-48 bg-white border rounded-lg shadow-lg z-10">
                        <button type="button" @click="$refs.cameraInput.click()" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50">
                            📷 Ambil Foto
                        </button>
                        <button type="button" @click="$refs.galleryInput.click()" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50">
                            🖼️ Pilih dari Galeri
                        </button>
                    </div>
                </div>

                <!-- Input khusus kamera (langsung buka kamera di HP) -->
                <input type="file" accept="image/*" capture="environment" x-ref="cameraInput" class="hidden"
                       @change="if ($event.target.files[0]) scan($event.target.files[0]); $event.target.value = '';">

                <!-- Tanpa 'capture' => buka galeri/file picker biasa -->
                <input type="file" accept="image/*" x-ref="galleryInput" class="hidden"
                       @change="if ($event.target.files[0]) scan($event.target.files[0]); $event.target.value = '';">

                <p x-show="ocrError" x-cloak x-text="ocrError" class="text-xs text-red-500 mb-2"></p>
                <p x-show="ocrInfo" x-cloak x-text="ocrInfo" class="text-xs text-emerald-600 mb-2"></p>
                <div x-show="receiptItems.length" x-cloak class="mb-3 border rounded-lg p-3 text-xs">
                    <label class="flex items-center gap-2 font-medium text-gray-600">
                        <input type="checkbox" x-model="saveReceipt">
                        <span>Simpan rincian barang (<span x-text="receiptItems.length"></span>)</span>
                    </label>
                    <ul class="mt-2 space-y-0.5 text-gray-500 max-h-32 overflow-auto">
                        <template x-for="(r, idx) in receiptItems" :key="idx">
                            <li class="flex justify-between gap-3">
                                <span x-text="(r.qty > 1 ? r.qty + '× ' : '') + r.name"></span>
                                <span class="shrink-0" x-text="r.price != null ? Number(r.price).toLocaleString('id-ID') : ''"></span>
                            </li>
                        </template>
                    </ul>
                    <p class="mt-2 text-gray-400">Hanya sebagai keterangan, tidak ikut perhitungan. Yang tercatat adalah jumlah total di bawah.</p>
                </div>
                <details x-show="ocrText && !receiptItems.length" x-cloak :open="!ocrInfo.startsWith('Terbaca')" class="mb-3">
                    <summary class="text-xs text-gray-400 cursor-pointer">Lihat teks hasil scan</summary>
                    <div class="mt-1 text-xs text-gray-400 bg-gray-50 border rounded p-2 max-h-32 overflow-auto whitespace-pre-wrap" x-text="ocrText"></div>
                </details>

                <form method="POST" action="{{ route('expenses.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="book_id" value="{{ $currentBook->id }}">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Jenis Pengeluaran</label>
                        <select name="category_id" x-model="categoryId" @change="categoryChanged()" required class="w-full border rounded-lg px-3 py-2">
                            @foreach($sheetCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="relative">
                        <label class="block text-xs text-gray-500 mb-1">Item</label>
                        <input type="text" name="item_name" x-model="itemQuery" required autocomplete="off"
                               @focus="itemMenuOpen = true" @input="itemMenuOpen = true; itemAuto = false"
                               placeholder="misal: Galon, Gas" class="border rounded-lg px-3 py-2 w-full">
                        <div x-show="itemMenuOpen" @click.outside="itemMenuOpen = false" x-cloak
                             class="absolute z-10 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-40 overflow-auto">
                            <template x-for="opt in filteredItems" :key="opt">
                                <button type="button" @click="pickItem(opt); itemAuto = false" x-text="opt"
                                        class="block w-full text-left px-3 py-1.5 text-sm hover:bg-indigo-50"></button>
                            </template>
                            <p x-show="filteredItems.length === 0" class="px-3 py-1.5 text-xs text-gray-400">Item baru — lanjut ketik lalu simpan</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Jumlah</label>
                        <input type="text" inputmode="numeric" x-model="amountDisplay" @input="formatAmount($event)"
                               placeholder="Rp 0" class="w-full border rounded-lg px-3 py-2.5 text-lg">
                        <input type="hidden" name="amount" x-ref="expAmount">
                        <input type="hidden" name="receipt" :value="receiptPayload">
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Tanggal</label>
                        <input type="date" name="date" x-ref="expDate" value="{{ now()->format('Y-m-d') }}" required class="w-full border rounded-lg px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Catatan (opsional)</label>
                        <input type="text" name="note" x-ref="expNote" placeholder="Catatan tambahan" class="w-full border rounded-lg px-3 py-2">
                    </div>

                    <button class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-medium">Simpan Pengeluaran</button>
                </form>

                <p class="text-xs text-gray-400 mt-3">Ketik nama item baru kalau belum ada di saran — otomatis kesimpan buat dipakai lagi. Hasil scan bisa meleset, cek ulang nominalnya sebelum simpan.</p>

            </div>
        </div>
    </div>
</div>
