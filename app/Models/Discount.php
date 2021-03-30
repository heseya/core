<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\SearchTypes\DiscountSearch;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory, Searchable;

    protected $casts = [
        'type' => DiscountType::class,
    ];

    protected array $searchable = [
        'name' => Like::class,
        'code' => Like::class,
        'search' => DiscountSearch::class,
    ];
}
