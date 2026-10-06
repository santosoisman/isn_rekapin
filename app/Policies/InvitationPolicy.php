<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    // Batalkan undangan: yang mengundang, atau owner buku
    public function cancel(User $user, Invitation $invitation): bool
    {
        return $invitation->invited_by === $user->id || $invitation->book->owner_id === $user->id;
    }
}
