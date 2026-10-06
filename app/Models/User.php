<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ownedBooks()
    {
        return $this->hasMany(Book::class, 'owner_id');
    }

    // Semua buku yang bisa diakses user (owner maupun kolaborator)
    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_members')
            ->withPivot('role')
            ->withTimestamps();
    }
}
