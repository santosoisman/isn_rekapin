<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => null,
            ]);
        }

        Auth::login($user, remember: true);

        // Otomatis proses semua undangan kolaborasi yang cocok dengan email ini
        $this->attachPendingInvitations($user);

        return redirect()->intended(route('dashboard'));
    }

    protected function attachPendingInvitations(User $user): void
    {
        $invitations = Invitation::where('email', $user->email)
            ->where('status', 'pending')
            ->get();

        foreach ($invitations as $invitation) {
            DB::table('book_members')->updateOrInsert(
                ['book_id' => $invitation->book_id, 'user_id' => $user->id],
                ['role' => 'member', 'created_at' => now(), 'updated_at' => now()]
            );

            $invitation->update(['status' => 'accepted']);

            // Buku dari undangan terakhir dijadikan buku aktif
            session(['current_book_id' => $invitation->book_id]);
        }
    }
}
