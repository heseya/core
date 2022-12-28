<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperAuthProvider
 */
class AuthProvider extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'key',
        'active',
        'client_id',
        'client_secret',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
