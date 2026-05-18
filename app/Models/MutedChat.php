<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutedChat extends Model
{
    protected $fillable = [
        'user_id',
        'muted_user_id',
    ];
}
