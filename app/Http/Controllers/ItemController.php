<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function update(Request $request, Item $item)
    {
        abort_unless($item->book->isMember($request->user()), 403);

        $data = $request->validate(['name' => 'required|string|max:255']);
        $item->update(['name' => $data['name']]);

        return back()->with('success', 'Item berhasil diperbarui.');
    }

    public function destroy(Request $request, Item $item)
    {
        abort_unless($item->book->isMember($request->user()), 403);

        if ($item->expenses()->exists()) {
            return back()->with('info', 'Item "' . $item->name . '" masih punya transaksi, tidak bisa dihapus.');
        }

        $item->delete();

        return back()->with('success', 'Item "' . $item->name . '" dihapus.');
    }
}
