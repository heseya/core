<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperOneTimeSecurityCode
 */
class OneTimeSecurityCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
