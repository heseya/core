<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Models;

use App\Models\Interfaces\Translatable;
use App\Models\Model;
use Spatie\Translatable\HasTranslations;
use Support\Enum\Status;

/**
 * @mixin IdeHelperSalesChannel
 */
final class SalesChannel extends Model implements Translatable
{
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
}
