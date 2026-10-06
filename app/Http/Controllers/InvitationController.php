<?php

namespace App\Http\Controllers;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InvitationController extends Controller
{
    public function store(Request $request)
    {
        $book = $request->attributes->get('currentBook');
        $this->authorize('invite', $book);

        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $invitation = Invitation::create([
            'book_id' => $book->id,
            'email' => $data['email'],
            'invited_by' => $request->user()->id,
            'status' => 'pending',
        ]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return back()->with('success', 'Undangan sudah dikirim ke ' . $invitation->email);
    }

    // Diakses lewat link di email undangan
    public function accept(string $token, Request $request)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->status === 'accepted') {
            return redirect()->route('dashboard')->with('info', 'Undangan ini sudah pernah diterima.');
        }

        if (!$request->user()) {
            session(['url.intended' => route('invitations.accept', $token)]);
            return redirect()->route('auth.google.redirect');
        }

        if (strcasecmp($request->user()->email, $invitation->email) !== 0) {
            abort(403, 'Undangan ini dikirim untuk email lain.');
        }

        DB::table('book_members')->updateOrInsert(
            ['book_id' => $invitation->book_id, 'user_id' => $request->user()->id],
            ['role' => 'member', 'created_at' => now(), 'updated_at' => now()]
        );

        $invitation->update(['status' => 'accepted']);

        session(['current_book_id' => $invitation->book_id]);

        return redirect()->route('dashboard')->with('success', 'Berhasil bergabung ke buku "' . $invitation->book->name . '"');
    }

    public function cancel(Invitation $invitation)
    {
        $this->authorize('cancel', $invitation);

        $invitation->delete();

        return back()->with('success', 'Undangan dibatalkan.');
    }
}
