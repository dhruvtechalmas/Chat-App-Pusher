<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupRead extends Model
{
    protected $fillable = [
        'user_id',
        'group_id',
        'last_read_at',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
    ];
}
