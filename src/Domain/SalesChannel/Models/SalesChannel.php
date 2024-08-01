<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Models;

use App\Models\App;
use App\Models\Interfaces\Translatable;
use App\Models\Model;
use App\Models\User;
use App\Traits\CustomHasTranslations;
use Domain\Language\Language;
use Domain\Organization\Models\Organization;
use Domain\PaymentMethods\Models\PaymentMethod;
use Domain\SalesChannel\Criteria\SalesChannelCountrySearch;
use Domain\SalesChannel\Enums\SalesChannelActivityType;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperSalesChannel
 */
final class SalesChannel extends Model implements Translatable
{
    use CustomHasTranslations;
    use HasCriteria;
    use HasFactory;

    public const HIDDEN_PERMISSION = 'sales_channels.show_hidden';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'status',
        'language_id',
        'published',
        'activity',
        'default',
        'price_map_id',

        // TODO: remove temp field
        'vat_rate',
    ];

    /** @var string[] */
    protected array $translatable = [
        'name',
    ];

    protected $casts = [
        'status' => SalesChannelStatus::class,
        'published' => 'array',
        'activity' => SalesChannelActivityType::class,
        'default' => 'bool',
    ];

    /** @var array<string, class-string> */
    protected array $criteria = [
        'country' => SalesChannelCountrySearch::class,
        'published' => Like::class,
        'sales_channels.published' => Like::class,
    ];

    /**
     * @return BelongsTo<Language, self>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return BelongsToMany<ShippingMethod>
     */
    public function shippingMethods(): BelongsToMany
    {
        return $this->belongsToMany(ShippingMethod::class, 'sales_channel_shipping_method');
    }

    /**
     * @return BelongsToMany<PaymentMethod>
     */
    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMethod::class, 'sales_channel_payment_method');
    }

    /**
     * @return HasMany<Organization>
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    /**
     * @param Builder<self> $query
     */
    public function scopeHiddenInOrganization(Builder $query, App|User $user): void
    {
        $query->orWhereHas('organizations', function (Builder $query) use ($user): void {
            $query->whereHas('users', function (Builder $query) use ($user): void {
                $query->where('id', '=', $user->getKey());
            });
        });
    }
}
