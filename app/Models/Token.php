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

    protected $dates = [
        'expires_at',
    ];

    public function invalidate()
    {
        $this->update(['invalidated' => true]);
    }
}
