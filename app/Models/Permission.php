<?php

namespace App\Models;

use App\SearchTypes\PermissionSearch;
use App\Traits\HasUuid;
use Heseya\Searchable\Traits\Searchable;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @mixin IdeHelperPermission
 */
class Permission extends SpatiePermission
{
    use Searchable, HasUuid;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'guard_name',
    ];

    protected array $searchable = [
        'assignable' => PermissionSearch::class,
    ];
}
