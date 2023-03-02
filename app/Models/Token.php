<?php

namespace App\Models;

use App\Traits\HasUuid;

/**
 * @mixin IdeHelperToken
 */
class Token extends Model
{
    use HasUuid;

    protected $fillable = [
        'id',
        'invalidated',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'date',
    ];

    public function invalidate(): void
    {
        $this->update(['invalidated' => true]);
    }
}
