<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Models;

use App\Models\Country;
use App\Models\Interfaces\Translatable;
use App\Models\Model;
use App\Models\Product;
use App\Traits\CustomHasTranslations;
use Domain\Language\Language;
use Domain\Product\Models\ProductSalesChannel;
use Domain\SalesChannel\Criteria\CountrySearch;
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

    protected const HIDDEN_PERMISSION = 'sales_channels.show_hidden';

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

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            (new ProductSalesChannel())->getTable(),
            'sales_channel_id',
            'product_id',
        )
            ->using(ProductSalesChannel::class)
            ->withPivot(['active', 'public']);
    }
}
