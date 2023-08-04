<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\RoleAssignableSearch;
use App\Criteria\RoleSearch;
use App\Criteria\WhereInIds;
use App\Enums\RoleType;
use App\Traits\HasDiscountConditions;
use App\Traits\HasMetadata;
use App\Traits\HasUuid;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * @property RoleType $type
 * @property string $name
 * @property bool $is_registration_role
 *
 * @mixin IdeHelperRole
 */
class Role extends SpatieRole
{
    use HasCriteria;
    use HasDiscountConditions;
    use HasFactory;
    use HasMetadata;
    use HasUuid;

    protected $fillable = [
        'name',
        'description',
        'guard_name',
        'is_registration_role',
    ];

    protected $casts = [
        'type' => RoleType::class,
        'is_registration_role' => 'bool',
    ];

    protected array $criteria = [
        'name' => Like::class,
        'description' => Like::class,
        'search' => RoleSearch::class,
        'assignable' => RoleAssignableSearch::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
    ];

    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_roles'),
            PermissionRegistrar::$pivotRole,
            config('permission.column_names.model_morph_key'),
        );
    }

    public function isAssignable(): bool
    {
        /** @var User|App|null $user */
        $user = Auth::user();

        return $user !== null
            && $this->type !== RoleType::UNAUTHENTICATED
            && $this->type !== RoleType::AUTHENTICATED
            && $user->hasAllPermissions($this->getAllPermissions());
    }

    protected static function booted(): void
    {
        static::addGlobalScope('order', function (Builder $builder): void {
            $builder->orderByRaw('type = ' . RoleType::OWNER->value . ' DESC, type ASC');
        });
    }
}
