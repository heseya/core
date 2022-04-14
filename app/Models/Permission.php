<?php

namespace App\Models;

use App\Criteria\PermissionSearch;
use App\Traits\HasUuid;
use Heseya\Searchable\Traits\HasCriteria;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property string $name
 *
 * @mixin IdeHelperPermission
 */
class Permission extends SpatiePermission
{
    use HasCriteria, HasUuid;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'guard_name',
    ];

    protected array $criteria = [
        'assignable' => PermissionSearch::class,
    ];
}
