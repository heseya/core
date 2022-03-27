<?php

namespace App\Models;

use App\Criteria\RoleAssignableSearch;
use App\Criteria\RoleSearch;
use App\Enums\RoleType;
use App\Traits\HasUuid;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @mixin IdeHelperRole
 */
class Role extends SpatieRole implements AuditableContract
{
    use HasCriteria, HasUuid, HasFactory, Auditable;

    protected $fillable = [
        'name',
        'description',
        'guard_name',
    ];

    protected $casts = [
        'type' => RoleType::class,
    ];

    protected array $criteria = [
        'name' => Like::class,
        'description' => Like::class,
        'search' => RoleSearch::class,
        'assignable' => RoleAssignableSearch::class,
    ];
}
