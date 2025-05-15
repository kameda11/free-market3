<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'address_id',
        'exhibition_id',
        'amount',
        'payment_method',
        'user_id',
    ];

    /**
     * リレーション: 購入は1つの住所に属する
     */
    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function exhibition()
    {
        return $this->belongsTo(Exhibition::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
