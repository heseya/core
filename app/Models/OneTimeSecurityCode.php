<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

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

    protected $dates = [
        'expires_at',
    ];

    public function getCodeAttribute($value): string
    {
        return Crypt::decryptString($value);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
