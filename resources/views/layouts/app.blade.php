<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rekapin')</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#4f46e5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Rekapin">

    {{-- Tema: ikut device (default) atau pilihan manual. Dipasang sebelum CSS biar gak kedip. --}}
    <script>
        (function () {
            var root = document.documentElement;
            var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
            function pref() { try { return localStorage.getItem('theme') || 'system'; } catch (e) { return 'system'; } }
            function apply() {
                var t = pref();
                var dark = t === 'dark' || (t !== 'light' && mq && mq.matches);
                root.classList.toggle('dark', dark);
                var m = document.querySelector('meta[name=theme-color]');
                if (m) m.setAttribute('content', dark ? '#0b1120' : '#4f46e5');
            }
            window.getTheme = pref;
            window.setTheme = function (t) { try { localStorage.setItem('theme', t); } catch (e) {} apply(); };
            apply();
            if (mq && mq.addEventListener) mq.addEventListener('change', apply);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen" x-data="{ bookMenuOpen: false }">
    <nav class="bg-white border-b px-4 sm:px-6 py-3 sticky top-0 z-20">
        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-bold text-lg text-indigo-600">
                <img src="{{ asset('icon-32.png') }}" alt="" class="w-6 h-6 rounded">
                Rekapin
            </a>

            <div class="flex items-center gap-2 sm:gap-4 min-w-0">
                @if(isset($currentBook))
                    <!-- Pilih buku (mobile & desktop) -->
                    <div class="relative min-w-0">
                        <button @click="bookMenuOpen = !bookMenuOpen" class="flex items-center gap-1 max-w-[11rem] sm:max-w-none text-sm border rounded-full sm:rounded px-3 py-1.5 bg-gray-50">
                            <span class="truncate">📒 {{ $currentBook->name }}</span>
                            <svg class="w-3 h-3 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="bookMenuOpen" @click.outside="bookMenuOpen = false" x-cloak
                             class="absolute right-0 mt-2 w-56 bg-white border rounded-lg shadow-lg z-30 overflow-hidden">
                            @foreach(auth()->user()->books as $b)
                                <form method="POST" action="{{ route('books.switch', $b) }}">
                                    @csrf
                                    <button class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 {{ $b->id === $currentBook->id ? 'font-semibold text-indigo-600' : '' }}">
                                        {{ $b->name }}
                                    </button>
                                </form>
                            @endforeach
                            <a href="{{ route('books.create') }}" class="block px-4 py-2 text-sm text-indigo-600 border-t hover:bg-gray-100">+ Buku baru</a>
                            <a href="{{ route('books.edit') }}" class="block px-4 py-2 text-sm text-gray-600 border-t hover:bg-gray-100">⚙ Kelola buku</a>
                        </div>
                    </div>

                    <!-- Menu desktop (di HP diganti bottom nav) -->
                    <div class="hidden sm:flex items-center gap-4">
                        <a href="{{ route('transactions.index') }}" class="text-sm text-gray-600 hover:text-indigo-600">Transaksi</a>
                        <a href="{{ route('history.calendar') }}" class="text-sm text-gray-600 hover:text-indigo-600">History</a>
                        <a href="{{ route('books.edit') }}" class="text-sm text-gray-600 hover:text-indigo-600">Kelola Buku</a>
                        <button type="button" @click="$dispatch('open-add')" class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded font-medium hover:bg-indigo-700">+ Transaksi</button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="text-sm text-gray-500 hover:text-red-600">Keluar</button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-sm text-gray-500 hover:text-red-600">Keluar</button>
                    </form>
                @endif
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto p-4 sm:p-6 {{ isset($currentBook) ? 'pb-28' : '' }} sm:pb-6">
        @if(session('success'))
            <div class="mb-4 bg-green-100 text-green-700 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="mb-4 bg-blue-100 text-blue-700 px-4 py-2 rounded text-sm">{{ session('info') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 bg-red-100 text-red-700 px-4 py-2 rounded text-sm">{{ $errors->first() }}</div>
        @endif

        @yield('content')
    </main>

    @if(isset($currentBook))
        @include('partials.add-sheet')

        <!-- Bottom navigation ala app native (hanya di HP) -->
        <nav class="sm:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t" style="padding-bottom: env(safe-area-inset-bottom)">
            <div class="grid grid-cols-5 items-end h-16">
                @php
                    $navItems = [
                        ['route' => 'dashboard', 'label' => 'Beranda', 'icon' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10'],
                        ['route' => 'transactions.index', 'label' => 'Transaksi', 'icon' => 'M4 6h16M4 12h16M4 18h10'],
                    ];
                    $navItemsRight = [
                        ['route' => 'history.calendar', 'label' => 'History', 'icon' => 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z'],
                        ['route' => 'books.edit', 'label' => 'Kelola', 'icon' => 'M12 6.25v13m0-13C10.8 5.48 9.2 5 7.5 5S4.2 5.48 3 6.25v13C4.2 18.48 5.8 18 7.5 18s3.3.48 4.5 1.25m0-13C13.2 5.48 14.8 5 16.5 5c1.7 0 3.3.48 4.5 1.25v13C19.8 18.48 18.2 18 16.5 18c-1.7 0-3.3.48-4.5 1.25'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}" class="flex flex-col items-center justify-center h-full gap-0.5 {{ request()->routeIs($item['route'].'*') ? 'text-indigo-600' : 'text-gray-400' }}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        <span class="text-[11px] font-medium">{{ $item['label'] }}</span>
                    </a>
                @endforeach

                <!-- Tombol tambah di tengah -->
                <div class="flex justify-center h-full">
                    <button type="button" @click="$dispatch('open-add')" aria-label="Tambah transaksi"
                            class="-mt-6 w-14 h-14 rounded-full bg-indigo-600 text-white shadow-lg flex items-center justify-center active:scale-95 transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    </button>
                </div>

                @foreach($navItemsRight as $item)
                    <a href="{{ route($item['route']) }}" class="flex flex-col items-center justify-center h-full gap-0.5 {{ request()->routeIs($item['route'].'*') ? 'text-indigo-600' : 'text-gray-400' }}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                        <span class="text-[11px] font-medium">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    @endif
</body>
</html>
