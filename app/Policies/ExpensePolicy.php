<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function update(User $user, Expense $expense): bool
    {
        return $expense->user_id === $user->id || $expense->book->owner_id === $user->id;
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->update($user, $expense);
    }
}
