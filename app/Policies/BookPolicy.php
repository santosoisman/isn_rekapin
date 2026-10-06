<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    public function view(User $user, Book $book): bool
    {
        return $book->isMember($user);
    }

    // Rename buku, kelola member, dsb — khusus owner
    public function update(User $user, Book $book): bool
    {
        return $book->owner_id === $user->id;
    }

    public function delete(User $user, Book $book): bool
    {
        return $book->owner_id === $user->id;
    }

    // Semua member boleh mengundang orang lain
    public function invite(User $user, Book $book): bool
    {
        return $book->isMember($user);
    }

    // Hanya owner yang boleh mengeluarkan member
    public function removeMember(User $user, Book $book): bool
    {
        return $book->owner_id === $user->id;
    }
}
