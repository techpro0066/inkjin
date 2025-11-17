<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'question',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];
}
