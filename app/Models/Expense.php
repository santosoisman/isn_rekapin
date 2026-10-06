<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['book_id', 'category_id', 'item_id', 'user_id', 'amount', 'date', 'note', 'receipt'];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'receipt' => 'array',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
