<?php

declare(strict_types=1);

namespace Domain\Organization\Models;

use App\Enums\SavedAddressType;
use App\Models\Address;
use App\Models\Model;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperOrganizationSavedAddress
 */
final class OrganizationSavedAddress extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'default',
        'name',
        'address_id',
        'organization_id',
        'type',
        'change_version',
    ];
    protected $casts = [
        'default' => 'bool',
        'name' => 'string',
        'type' => SavedAddressType::class,
    ];

    /**
     * @return BelongsTo<Address, self>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return BelongsTo<Organization, self>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
