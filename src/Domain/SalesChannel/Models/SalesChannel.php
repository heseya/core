<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Models;

use App\Models\Model;
use Brick\Math\BigDecimal;
use Support\Enum\Status;

final class SalesChannel extends Model
{
    protected $fillable = [
        'id',
        'name',
        'slug',
        'status',
        'default',
        'countries_block_list',
        'default_currency_id',
        'default_language_id',

        // TODO: remove temp field
        'vat_rate',
    ];

    protected $casts = [
        'status' => Status::class,
        'default' => 'bool',
        'countries_block_list' => 'bool',

        // TODO: remove temp field
        'vat_rate' => BigDecimal::class,
    ];
}
