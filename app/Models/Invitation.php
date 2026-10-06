<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invitation extends Model
{
    protected $fillable = ['book_id', 'email', 'token', 'invited_by', 'status'];

    protected static function booted()
    {
        static::creating(function (Invitation $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(48);
            }
        });
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
