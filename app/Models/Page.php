<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Page extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'public',
        'content_html',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
