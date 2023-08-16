<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Models;

use App\Models\Country;
use App\Models\Interfaces\Translatable;
use App\Models\Model;
use Domain\SalesChannel\Criteria\CountrySearch;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use Support\Enum\Status;

/**
 * @mixin IdeHelperSalesChannel
 */
final class SalesChannel extends Model implements Translatable
{
    use HasCriteria;
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'status',
        'countries_block_list',
        'default_currency',
        'default_language_id',

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
    ];

    /** @var array<string, class-string> */
    protected array $criteria = [
        'country' => CountrySearch::class,
    ];

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
}
