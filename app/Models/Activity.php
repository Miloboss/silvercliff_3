<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['title', 'description', 'price_thb', 'time_slots', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'time_slots' => 'array',
    ];}
