<?php

namespace App\Models;

use App\Criteria\DiscountSearch;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Enums\DiscountType;
use App\Traits\HasMetadata;
use Carbon\Carbon;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperDiscount
 */
class Discount extends Model implements AuditableContract
{
    use HasFactory, HasCriteria, SoftDeletes, Auditable, HasMetadata;

    protected $fillable = [
        'description',
        'code',
        'discount',
        'type',
        'max_uses',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'type' => DiscountType::class,
    ];

    protected $dates = [
        'starts_at',
        'expires_at',
    ];

    protected array $criteria = [
        'description' => Like::class,
        'code' => Like::class,
        'search' => DiscountSearch::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
    ];

    public function getUsesAttribute(): int
    {
        return $this->orders->count();
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_discounts');
    }

    public function getAvailableAttribute(): bool
    {
        if ($this->uses >= $this->max_uses) {
            return false;
        }

        $today = Carbon::now();

        if ($this->starts_at !== null && $this->expires_at !== null) {
            return $today >= $this->starts_at && $today <= $this->expires_at;
        }

        if ($this->starts_at !== null) {
            return $today >= $this->starts_at;
        }

        if ($this->expires_at !== null) {
            return $today <= $this->expires_at;
        }

        return true;
    }
}
