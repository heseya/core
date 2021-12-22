<?php

namespace App\Models;

use App\SearchTypes\ItemSearch;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Heseya\Sortable\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperItem
 */
class Item extends Model implements AuditableContract
{
    use SoftDeletes, HasFactory, Searchable, Sortable, Auditable;

    protected $fillable = [
        'name',
        'sku',
    ];

    protected array $searchable = [
        'name' => Like::class,
        'sku' => Like::class,
        'search' => ItemSearch::class,
    ];

    protected array $sortable = [
        'name',
        'sku',
        'created_at',
        'updated_at',
    ];

    public function getQuantityAttribute(): float
    {
        return $this->deposits->sum('quantity');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }
}
