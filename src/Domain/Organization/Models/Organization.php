<?php

declare(strict_types=1);

namespace Domain\Organization\Models;

use App\Enums\SavedAddressType;
use App\Models\Address;
use App\Models\Model;
use App\Models\User;
use App\Traits\HasDiscountConditions;
use Domain\Consent\Models\Consent;
use Domain\Consent\Models\ConsentOrganization;
use Domain\SalesChannel\Models\SalesChannel;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperOrganization
 */
final class Organization extends Model
{
    use HasCriteria;
    use HasDiscountConditions;
    use HasFactory;

    protected $fillable = [
        'id',
        'change_version',
        'client_id',
        'billing_email',
        'billing_address_id',
        'sales_channel_id',
        'creator_email',
        'is_complete',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_complete' => 'boolean',
    ];

    /** @var string[] */
    protected array $criteria = [
        'is_complete',
    ];

    /**
     * @return BelongsTo<Address, self>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    /**
     * @return HasMany<OrganizationSavedAddress>
     */
    public function deliveryAddresses(): HasMany
    {
        return $this->hasMany(OrganizationSavedAddress::class)
            ->where('type', '=', SavedAddressType::SHIPPING);
    }

    /**
     * @return HasMany<OrganizationSavedAddress>
     */
    public function invoiceAddresses(): HasMany
    {
        return $this->hasMany(OrganizationSavedAddress::class)
            ->where('type', '=', SavedAddressType::BILLING);
    }

    /**
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user');
    }

    /**
     * @return BelongsTo<SalesChannel, self>
     */
    public function salesChannel(): BelongsTo
    {
        return $this->belongsTo(SalesChannel::class);
    }

    /**
     * @return BelongsToMany<Consent>
     */
    public function consents(): BelongsToMany
    {
        return $this->belongsToMany(Consent::class)
            ->using(ConsentOrganization::class)
            ->withPivot('value');
    }
}
