<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'question',
        'type',
        'options',
        'status',
        'max_images',
    ];

    protected $casts = [
        'status' => 'string',
        'options' => 'array',
        'max_images' => 'integer',
    ];
}
