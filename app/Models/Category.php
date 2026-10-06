<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['book_id', 'name', 'type'];

    const TYPES = [
        'dasar' => 'Pengeluaran Dasar',
        'tanpa_rencana' => 'Pengeluaran Tanpa Rencana',
        'jajan' => 'Pengeluaran Jajan',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
