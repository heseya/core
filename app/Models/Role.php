<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\RoleAssignableSearch;
use App\Criteria\RoleSearch;
use App\Enums\RoleType;
use App\Traits\HasDiscountConditions;
use App\Traits\HasMetadata;
use App\Traits\HasUuid;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property RoleType $type
 *
 * @mixin IdeHelperRole
 */
class Role extends SpatieRole implements AuditableContract
{
    use HasCriteria, HasUuid, HasFactory, Auditable, HasMetadata, HasDiscountConditions;

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
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('order', function (Builder $builder): void {
            $builder->orderByRaw('type = ' . RoleType::OWNER . ' DESC, type ASC');
        });
    }
}
