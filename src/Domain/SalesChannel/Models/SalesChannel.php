<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Models;

use App\Models\Country;
use App\Models\Interfaces\Translatable;
use App\Models\Model;
use App\Traits\CustomHasTranslations;
use Domain\Language\Language;
use Domain\SalesChannel\Criteria\CountrySearch;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Support\Enum\Status;

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
        'countries_block_list',
        'default_currency',
        'default_language_id',
        'published',

        // TODO: remove temp field
        'vat_rate',
    ];
    /** @var string[] */
    protected array $translatable = [
        'name',
    ];
    protected $casts = [
        'status' => Status::class,
        'countries_block_list' => 'bool',
        'published' => 'array',
    ];
    /** @var array<string, class-string> */
    protected array $criteria = [
        'country' => CountrySearch::class,
        'published' => Like::class,
        'sales_channels.published' => Like::class,
    ];

    /**
     * @return BelongsTo<Language, self>
     */
    public function defaultLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return BelongsToMany<Country>
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(
            Country::class,
            'sales_channels_countries',
            'sales_channel_id',
            'country_code',
            'id',
            'code',
        );
    }

    /**
     * @return BelongsToMany<ShippingMethod>
     */
    public function shippingMethods(): BelongsToMany
    {
        return $this->belongsToMany(
            ShippingMethod::class,
            'sales_channel_shipping_method',
            'sales_channel_id',
            'shipping_method_id',
            'id',
            'id',
        );
    }
}
