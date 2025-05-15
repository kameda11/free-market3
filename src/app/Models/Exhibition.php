<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exhibition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'detail',
        'category',
        'product_image',
        'condition',
        'price',
        'sold',
        'user_id',
        'brand',
    ];

    protected $casts = [
        'price' => 'integer',
        'sold' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function purchase()
    {
        return $this->hasOne(Purchase::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }
}
