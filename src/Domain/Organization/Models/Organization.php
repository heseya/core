<?php

declare(strict_types=1);

namespace Domain\Organization\Models;

use App\Models\Address;
use App\Models\Model;
use App\Models\User;
use Domain\Organization\Enums\OrganizationStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Organization extends Model
{
    use HasCriteria;
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'phone',
        'email',
        'address_id',
        'status',
        'sales_channel_id',
    ];

    protected $casts = [
        'status' => OrganizationStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** @var string[] */
    protected array $criteria = [
        'status',
    ];

    /**
     * @return BelongsTo<Address, self>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * @return BelongsToMany<User>
     */
    public function assistants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'assistant_organization', 'organization_id', 'assistant_id');
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
