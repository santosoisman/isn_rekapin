@extends('layouts.app')
@section('title', 'Buat Buku Keuangan')
@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow">
    <h1 class="text-xl font-semibold mb-4">Buat Buku Keuangan</h1>
    <form method="POST" action="{{ route('books.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm text-gray-600 mb-1">Nama Buku</label>
            <input type="text" name="name" required placeholder="Misal: Keuangan Keluarga"
                   class="w-full border rounded px-3 py-2">
        </div>
        <button class="w-full bg-indigo-600 text-white py-2 rounded font-medium hover:bg-indigo-700">
            Buat
        </button>
    </form>
</div>
@endsection
