<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'price', 'ingredients', 'rating', 'description', 'stock', 'picture', 'picture_public_id', 'type'
    ];

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => "timestamp",
        'deleted_at' => "timestamp"
    ];

    protected $hidden = [
        'picture_public_id'
    ];
}
