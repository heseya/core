<?php

namespace App\Models;

use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @OA\Schema ()
 *
 * @mixin IdeHelperSetting
 */
class Setting extends Model implements AuditableContract
{
    use Auditable;

    /**
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="setting-name",
     * )
     *
     * @OA\Property(
     *   property="value",
     *   type="string",
     * )
     *
     * @OA\Property(
     *   property="public",
     *   type="boolean",
     * )
     *
     * @OA\Property(
     *   property="permanent",
     *   type="boolean",
     * )
     */

    protected $fillable = [
        'name',
        'value',
        'public',
    ];

    protected $casts = [
        'public' => 'bool',
    ];

    public function getPermanentAttribute(): bool
    {
        return config('settings.' . $this->name) !== null;
    }
}
