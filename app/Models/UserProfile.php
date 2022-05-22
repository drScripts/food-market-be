<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'address', 'phone_number', 'house_number', 'city', 'profile_picture', 'picture_public_id'
    ];


    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => "timestamp",
        'deleted_at' => "timestamp"
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'picture_public_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
