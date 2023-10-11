<?php

declare(strict_types=1);

namespace Domain\Organization\Models;

use App\Models\Address;
use App\Models\Model;
use App\Models\User;
use Domain\Organization\Enums\OrganizationStatus;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    ];

    protected $casts = [
        'status' => OrganizationStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @var string[]
     */
    protected array $criteria = [
        'status',
    ];

    /**
     * @return HasOne<Address>
     */
    public function address(): HasOne
    {
        return $this->hasOne(Address::class, 'id', 'address_id');
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
}
