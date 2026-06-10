<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotRegistered extends Model
{
    protected $table = 'user_not_registered';

    protected $fillable = [
        'email',
        'country',
        'hear_about_us',
    ];
}
