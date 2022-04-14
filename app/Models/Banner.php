<?php

namespace App\Models;

use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperBanner
 */
class Banner extends Model
{
    use HasFactory,
        HasCriteria;

    protected $fillable = [
        'slug',
        'url',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected array $criteria = [
        'slug' => Like::class,
    ];

    public function responsiveMedia(): HasMany
    {
        return $this->hasMany(ResponsiveMedia::class, 'banner_id')
            ->orderBy('order');
    }
}
