@extends('layouts.app')
@section('title', 'Kelola Buku')
@section('content')
<div class="space-y-6">

    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold">Buku Saya</h2>
            <a href="{{ route('books.create') }}" class="text-sm text-indigo-600 hover:underline">+ Buku baru</a>
        </div>
        <ul class="divide-y text-sm">
            @foreach(auth()->user()->books as $b)
                @php $isOwner = $b->owner_id === auth()->id(); @endphp
                <li class="py-3" x-data="{ confirming: false }">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium">📒 {{ $b->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $isOwner ? 'Owner' : 'Anggota' }}
                                @if($b->id === $book->id) · <span class="text-indigo-600">sedang dipakai</span> @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            @if($b->id !== $book->id)
                                <form method="POST" action="{{ route('books.switch', $b) }}">
                                    @csrf
                                    <button class="text-xs text-indigo-600 hover:underline">Buka</button>
                                </form>
                            @endif
                            <button type="button" @click="confirming = !confirming" class="text-xs text-red-600 hover:underline">
                                {{ $isOwner ? 'Hapus' : 'Keluar' }}
                            </button>
                        </div>
                    </div>

                    @if($isOwner)
                        <form x-show="confirming" x-cloak method="POST" action="{{ route('books.destroy', $b) }}" class="mt-3 border border-red-200 rounded-lg p-3 space-y-2">
                            @csrf @method('DELETE')
                            <p class="text-xs text-red-600">Semua pemasukan, pengeluaran, kategori, dan anggota di buku ini ikut terhapus permanen. Ketik nama buku untuk konfirmasi:</p>
                            <input type="text" name="confirm_name" placeholder="{{ $b->name }}" required autocomplete="off" class="w-full border rounded px-3 py-2 text-sm">
                            <div class="flex gap-2">
                                <button class="text-xs bg-red-600 text-white px-3 py-1.5 rounded">Hapus Buku</button>
                                <button type="button" @click="confirming = false" class="text-xs px-3 py-1.5 rounded border">Batal</button>
                            </div>
                        </form>
                    @else
                        <form x-show="confirming" x-cloak method="POST" action="{{ route('books.leave', $b) }}" class="mt-3 border border-red-200 rounded-lg p-3 space-y-2">
                            @csrf @method('DELETE')
                            <p class="text-xs text-red-600">Kamu tidak akan bisa melihat buku ini lagi sampai diundang ulang oleh owner.</p>
                            <div class="flex gap-2">
                                <button class="text-xs bg-red-600 text-white px-3 py-1.5 rounded">Keluar dari Buku</button>
                                <button type="button" @click="confirming = false" class="text-xs px-3 py-1.5 rounded border">Batal</button>
                            </div>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold mb-3">Nama Buku Ini</h2>
        <form method="POST" action="{{ route('books.update') }}" class="flex gap-3">
            @csrf @method('PUT')
            <input type="text" name="name" value="{{ $book->name }}" class="flex-1 border rounded px-3 py-2">
            <button class="bg-indigo-600 text-white px-4 rounded font-medium">Simpan</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold mb-3">Anggota</h2>
        <ul class="divide-y text-sm">
            @foreach($book->members as $member)
                <li class="py-2 flex items-center justify-between">
                    <span>{{ $member->name }} ({{ $member->email }})
                        @if($member->id === $book->owner_id)
                            <span class="text-xs text-indigo-600">Owner</span>
                        @endif
                    </span>
                    @if($member->id !== $book->owner_id && $book->owner_id === auth()->id())
                        <form method="POST" action="{{ route('books.members.remove', $member) }}" onsubmit="return confirm('Keluarkan {{ $member->name }} dari buku ini?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:underline">Keluarkan</button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold mb-3">Ajak Kolaborasi</h2>
        <form method="POST" action="{{ route('invitations.store') }}" class="flex gap-3">
            @csrf
            <input type="email" name="email" placeholder="pasangan@gmail.com" required class="flex-1 min-w-0 border rounded px-3 py-2">
            <button class="bg-indigo-600 text-white px-4 rounded font-medium">Undang</button>
        </form>
        <p class="text-xs text-gray-500 mt-2">Begitu penerima login dengan email tsb, otomatis jadi anggota buku ini.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold mb-3">Undangan Menunggu</h2>
        <ul class="divide-y text-sm">
            @forelse($book->invitations as $inv)
                <li class="py-2 flex items-center justify-between">
                    <span>{{ $inv->email }}</span>
                    <form method="POST" action="{{ route('invitations.cancel', $inv) }}">
                        @csrf @method('DELETE')
                        <button class="text-xs text-red-600 hover:underline">Batalkan</button>
                    </form>
                </li>
            @empty
                <li class="py-2 text-gray-400">Tidak ada undangan pending.</li>
            @endforelse
        </ul>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold mb-3">Kategori Pengeluaran</h2>
        <p class="text-xs text-gray-400 mb-3">
            Bebas ditambah, diganti nama, atau dihapus sesuai kebutuhan. Kategori yang masih
            punya transaksi tidak bisa dihapus.
        </p>
        <ul class="divide-y text-sm mb-4">
            @foreach($book->categories as $cat)
                <li x-data="{ editing: false }" class="py-2">
                    <div x-show="!editing" class="flex items-center justify-between">
                        <span>{{ $cat->name }} <span class="text-xs text-gray-400">({{ $cat->expenses_count }} transaksi)</span></span>
                        <div class="flex items-center gap-3">
                            <button @click="editing = true" class="text-xs text-indigo-600 hover:underline">Ubah Nama</button>
                            <form method="POST" action="{{ route('categories.destroy', $cat) }}" onsubmit="return confirm('Hapus kategori {{ $cat->name }}?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-600 hover:underline">Hapus</button>
                            </form>
                        </div>
                    </div>
                    <form x-show="editing" x-cloak method="POST" action="{{ route('categories.update', $cat) }}" class="flex gap-2 mt-1">
                        @csrf @method('PUT')
                        <input type="text" name="name" value="{{ $cat->name }}" class="flex-1 border rounded px-2 py-1 text-sm">
                        <button class="text-xs bg-indigo-600 text-white px-3 py-1 rounded">Simpan</button>
                        <button type="button" @click="editing = false" class="text-xs px-3 py-1 rounded border">Batal</button>
                    </form>
                </li>
            @endforeach
        </ul>

        <form method="POST" action="{{ route('categories.store') }}" class="flex gap-3">
            @csrf
            <input type="text" name="name" placeholder="Nama kategori baru, misal: Cicilan" required class="flex-1 border rounded px-3 py-2 text-sm">
            <button class="bg-indigo-600 text-white px-4 rounded font-medium text-sm">Tambah</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold mb-3">Item Pengeluaran</h2>
        <p class="text-xs text-gray-400 mb-3">
            Item otomatis kesimpen tiap kali kamu ketik nama baru di form pengeluaran. Di sini
            kamu bisa beresin nama yang typo atau hapus yang sudah gak kepakai (kalau belum
            ada transaksinya).
        </p>

        @foreach($book->categories as $cat)
            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-600 mb-2">{{ $cat->name }}</h3>
                @if($cat->items->isEmpty())
                    <p class="text-xs text-gray-400 mb-2">Belum ada item di kategori ini.</p>
                @else
                    <ul class="divide-y text-sm border rounded">
                        @foreach($cat->items as $item)
                            <li x-data="{ editing: false }" class="px-3 py-2">
                                <div x-show="!editing" class="flex items-center justify-between">
                                    <span>{{ $item->name }} <span class="text-xs text-gray-400">({{ $item->expenses_count }} transaksi)</span></span>
                                    <div class="flex items-center gap-3">
                                        <button @click="editing = true" class="text-xs text-indigo-600 hover:underline">Ubah Nama</button>
                                        <form method="POST" action="{{ route('items.destroy', $item) }}" onsubmit="return confirm('Hapus item {{ $item->name }}?')">
                                            @csrf @method('DELETE')
                                            <button class="text-xs text-red-600 hover:underline">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                                <form x-show="editing" x-cloak method="POST" action="{{ route('items.update', $item) }}" class="flex gap-2 mt-1">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $item->name }}" class="flex-1 border rounded px-2 py-1 text-sm">
                                    <button class="text-xs bg-indigo-600 text-white px-3 py-1 rounded">Simpan</button>
                                    <button type="button" @click="editing = false" class="text-xs px-3 py-1 rounded border">Batal</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow p-4" x-data="{ t: getTheme() }">
        <h2 class="font-semibold mb-3">Tampilan</h2>
        <div class="grid grid-cols-3 gap-2 text-sm">
            <button type="button" @click="setTheme('system'); t = 'system'" :class="t === 'system' ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-600'" class="border rounded-lg py-2">Ikut device</button>
            <button type="button" @click="setTheme('light'); t = 'light'" :class="t === 'light' ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-600'" class="border rounded-lg py-2">Terang</button>
            <button type="button" @click="setTheme('dark'); t = 'dark'" :class="t === 'dark' ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-600'" class="border rounded-lg py-2">Gelap</button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <h2 class="font-semibold mb-1">Akun</h2>
        <p class="text-xs text-gray-500 mb-3">Masuk sebagai {{ auth()->user()->email }}</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full sm:w-auto border border-red-200 text-red-600 px-4 py-2 rounded font-medium text-sm">Keluar</button>
        </form>
    </div>

</div>
@endsection
