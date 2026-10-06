<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekapin — Catat Keuangan Bareng Pasangan</title>
    <meta name="description" content="Catat pemasukan & pengeluaran harianmu — dasar, tanpa rencana, sampai jajan. Ajak pasangan atau keluarga kolaborasi lewat undangan email.">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('icon-16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#4f46e5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Rekapin">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Rekapin">
    <meta property="og:title" content="Rekapin — Catat Keuangan Bareng Pasangan">
    <meta property="og:description" content="Catat pemasukan & pengeluaran harianmu — dasar, tanpa rencana, sampai jajan. Ajak pasangan atau keluarga kolaborasi lewat undangan email.">
    <meta property="og:image" content="{{ asset('og-image.png') }}">
    <meta property="og:url" content="{{ url('/') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Rekapin — Catat Keuangan Bareng Pasangan">
    <meta name="twitter:description" content="Catat pemasukan & pengeluaran harianmu, kolaborasi lewat email.">
    <meta name="twitter:image" content="{{ asset('og-image.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-800 antialiased">

    <!-- Navbar -->
    <header class="max-w-6xl mx-auto flex items-center justify-between px-6 py-6">
        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold">Rp</div>
            <span class="font-semibold text-lg">Rekapin</span>
        </div>
        @auth
            <a href="{{ route('dashboard') }}" class="text-sm font-medium bg-slate-900 text-white px-4 py-2 rounded-lg hover:bg-slate-700">
                Ke Dashboard
            </a>
        @else
            <a href="{{ route('auth.google.redirect') }}" class="text-sm font-medium border border-slate-200 px-4 py-2 rounded-lg hover:bg-slate-50">
                Masuk
            </a>
        @endauth
    </header>

    <!-- Hero -->
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-to-b from-indigo-50 via-white to-white"></div>
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-100 rounded-full blur-3xl opacity-60 -z-10"></div>
        <div class="absolute top-40 -left-24 w-72 h-72 bg-amber-100 rounded-full blur-3xl opacity-60 -z-10"></div>

        <div class="max-w-4xl mx-auto text-center px-6 pt-16 pb-20">
            <span class="inline-block bg-indigo-100 text-indigo-700 text-xs font-medium px-3 py-1 rounded-full mb-5">
                💸 Kelola uang, bukan cuma catat pengeluaran
            </span>
            <h1 class="text-4xl md:text-5xl font-bold tracking-tight text-slate-900 leading-tight">
                Uang Bulanan Lebih Terkontrol,<br class="hidden md:block"> Bareng Pasangan.
            </h1>
            <p class="mt-5 text-slate-500 text-lg max-w-2xl mx-auto">
                Catat pemasukan & pengeluaran harianmu — kebutuhan dasar, pengeluaran tanpa rencana,
                sampai jajan. Ajak pasangan atau keluarga kolaborasi cukup lewat undangan email.
            </p>

            <div class="mt-8 flex justify-center">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-3.5 rounded-xl font-medium shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition">
                        Ke Dashboard
                    </a>
                @else
                    <a href="{{ route('auth.google.redirect') }}"
                       class="inline-flex items-center gap-3 bg-white border border-slate-200 shadow-lg shadow-slate-100 px-6 py-3.5 rounded-xl font-medium hover:shadow-xl hover:-translate-y-0.5 transition">
                        <svg class="w-5 h-5" viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.6 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l6-6C34.7 5.6 29.6 3.5 24 3.5 12.7 3.5 3.5 12.7 3.5 24S12.7 44.5 24 44.5 44.5 35.3 44.5 24c0-1.2-.1-2.4-.3-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.6 15.9 18.9 13 24 13c3.1 0 5.9 1.2 8 3.1l6-6C34.7 5.6 29.6 3.5 24 3.5c-7.7 0-14.4 4.4-17.7 11.2z"/><path fill="#4CAF50" d="M24 44.5c5.5 0 10.5-2.1 14.3-5.6l-6.6-5.6c-2 1.5-4.7 2.6-7.7 2.6-5.3 0-9.7-3.4-11.3-8.1l-6.6 5.1C9.5 40 16.2 44.5 24 44.5z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.2-4.2 5.6l6.6 5.6C41.4 36.4 44.5 30.7 44.5 24c0-1.2-.1-2.4-.3-3.5z"/></svg>
                        Masuk dengan Google
                    </a>
                @endauth
            </div>
            <p class="mt-4 text-xs text-slate-400">Gratis. Nggak perlu kartu kredit.</p>
        </div>

        <!-- Preview mock card -->
        <div class="max-w-3xl mx-auto px-6 -mt-4 pb-4">
            <div class="bg-white border border-slate-100 rounded-2xl shadow-2xl shadow-slate-200 p-6">
                <div class="grid grid-cols-3 gap-4 mb-5">
                    <div class="bg-slate-50 rounded-xl p-4">
                        <p class="text-xs text-slate-400">Saldo</p>
                        <p class="text-lg font-bold text-emerald-600">Rp 2.450.000</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4">
                        <p class="text-xs text-slate-400">Pemasukan</p>
                        <p class="text-lg font-bold text-slate-700">Rp 8.000.000</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4">
                        <p class="text-xs text-slate-400">Pengeluaran</p>
                        <p class="text-lg font-bold text-slate-700">Rp 5.550.000</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm py-2 border-b border-slate-50">
                        <span class="text-slate-500">Belanja Bulanan — Dasar</span>
                        <span class="font-medium text-red-500">- Rp 850.000</span>
                    </div>
                    <div class="flex justify-between text-sm py-2 border-b border-slate-50">
                        <span class="text-slate-500">Kopi & Jajan Sore</span>
                        <span class="font-medium text-red-500">- Rp 35.000</span>
                    </div>
                    <div class="flex justify-between text-sm py-2">
                        <span class="text-slate-500">Servis Motor Mendadak — Tanpa Rencana</span>
                        <span class="font-medium text-red-500">- Rp 250.000</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="max-w-6xl mx-auto px-6 py-20">
        <div class="text-center mb-14">
            <h2 class="text-2xl md:text-3xl font-bold text-slate-900">Semua yang kamu butuh, dalam satu buku</h2>
            <p class="text-slate-500 mt-2">Simpel buat dipakai berdua, tetap rapi buat dilihat kapan aja.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="p-6 rounded-2xl border border-slate-100 hover:shadow-lg transition">
                <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m-4-4h8M3 12a9 9 0 1018 0 9 9 0 00-18 0z"/></svg>
                </div>
                <h3 class="font-semibold text-slate-900 mb-1">3 Kategori Pengeluaran</h3>
                <p class="text-sm text-slate-500">Pisahkan pengeluaran Dasar, Tanpa Rencana, dan Jajan biar tahu kemana uang benar-benar pergi.</p>
            </div>

            <div class="p-6 rounded-2xl border border-slate-100 hover:shadow-lg transition">
                <div class="w-11 h-11 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4"/></svg>
                </div>
                <h3 class="font-semibold text-slate-900 mb-1">Kolaborasi Lewat Email</h3>
                <p class="text-sm text-slate-500">Undang pasangan atau keluarga. Begitu mereka login dengan email yang diundang, langsung ikut mencatat bareng.</p>
            </div>

            <div class="p-6 rounded-2xl border border-slate-100 hover:shadow-lg transition">
                <div class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="font-semibold text-slate-900 mb-1">History Kalender</h3>
                <p class="text-sm text-slate-500">Lihat riwayat transaksi dalam tampilan kalender bulanan, klik satu tanggal buat lihat detailnya.</p>
            </div>
        </div>
    </section>

    <!-- Steps -->
    <section class="bg-slate-50 py-20">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-12">Mulai dalam 3 langkah</h2>
            <div class="grid md:grid-cols-3 gap-8 text-left">
                <div>
                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white text-sm font-bold flex items-center justify-center mb-3">1</div>
                    <h4 class="font-semibold text-slate-900 mb-1">Masuk dengan Google</h4>
                    <p class="text-sm text-slate-500">Tanpa daftar akun baru, tanpa hapal password.</p>
                </div>
                <div>
                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white text-sm font-bold flex items-center justify-center mb-3">2</div>
                    <h4 class="font-semibold text-slate-900 mb-1">Buat Buku Keuangan</h4>
                    <p class="text-sm text-slate-500">Kasih nama, misal "Keuangan Keluarga" atau "Dana Nikah".</p>
                </div>
                <div>
                    <div class="w-8 h-8 rounded-full bg-indigo-600 text-white text-sm font-bold flex items-center justify-center mb-3">3</div>
                    <h4 class="font-semibold text-slate-900 mb-1">Ajak Kolaborasi</h4>
                    <p class="text-sm text-slate-500">Undang pasangan lewat email, catat pengeluaran bareng-bareng.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="max-w-4xl mx-auto px-6 py-20 text-center">
        <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-3">Yuk, mulai rapikan keuangan hari ini.</h2>
        <p class="text-slate-500 mb-8">Gratis dan cuma butuh satu klik lewat akun Google-mu.</p>
        @guest
            <a href="{{ route('auth.google.redirect') }}" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-3.5 rounded-xl font-medium shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition">
                Masuk dengan Google
            </a>
        @endguest
    </section>

    <footer class="border-t border-slate-100 py-8 text-center text-xs text-slate-400">
        © {{ date('Y') }} Rekapin. Dibuat buat catat uang bareng orang tersayang.
    </footer>

</body>
</html>
