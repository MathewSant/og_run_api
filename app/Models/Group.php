<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'photo_id',
        'photo_url',
        'name',
        'description',
        'state',
        'city',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
