<?php

namespace App\Models;

use App\SearchTypes\RoleAssignableSearch;
use App\SearchTypes\RoleSearch;
use App\Traits\HasUuid;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @mixin IdeHelperRole
 */
class Role extends SpatieRole
{
    use Searchable, HasUuid, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'guard_name',
    ];

    protected array $searchable = [
        'name' => Like::class,
        'description' => Like::class,
        'search' => RoleSearch::class,
        'assignable' => RoleAssignableSearch::class,
    ];
}
