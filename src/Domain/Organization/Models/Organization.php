<?php

declare(strict_types=1);

namespace Domain\Organization\Models;

use App\Enums\SavedAddressType;
use App\Models\Address;
use App\Models\Model;
use App\Models\User;
use Domain\SalesChannel\Models\SalesChannel;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Organization extends Model
{
    use HasCriteria;
    use HasFactory;

    protected $fillable = [
        'id',
        'change_version',
        'client_id',
        'billing_email',
        'billing_address_id',
        'sales_channel_id',
        'creator_email',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
}
