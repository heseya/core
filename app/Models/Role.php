<?php

namespace App\Models;

use App\Enums\RoleType;
use App\SearchTypes\MetadataPrivateSearch;
use App\SearchTypes\MetadataSearch;
use App\SearchTypes\RoleAssignableSearch;
use App\SearchTypes\RoleSearch;
use App\Traits\HasMetadata;
use App\Traits\HasUuid;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @mixin IdeHelperRole
 */
class Role extends SpatieRole implements AuditableContract
{
    use Searchable, HasUuid, HasFactory, Auditable, HasMetadata;

    protected $fillable = [
        'name',
        'description',
        'guard_name',
    ];

    protected $casts = [
        'type' => RoleType::class,
    ];

    protected array $searchable = [
        'name' => Like::class,
        'description' => Like::class,
        'search' => RoleSearch::class,
        'assignable' => RoleAssignableSearch::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
    ];
}
