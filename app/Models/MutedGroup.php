<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutedGroup extends Model
{
    protected $fillable = [
        'user_id',
        'group_id'
    ];
}
