<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class App extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'url',
    ];
}
