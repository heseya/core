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
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function invalidate()
    {
        $this->update(['invalidated' => true]);
    }
}
