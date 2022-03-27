<?php

namespace App\Models;

use App\Criteria\ItemSearch;
use App\Criteria\WhereCreatedBefore;
use App\Criteria\WhereSoldOut;
use App\Traits\Sortable;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @mixin IdeHelperItem
 */
class Item extends Model implements AuditableContract
{
    use SoftDeletes, HasFactory, HasCriteria, Sortable, Auditable;

    protected $fillable = [
        'name',
        'sku',
        'quantity',
    ];

    protected array $criteria = [
        'name' => Like::class,
        'sku' => Like::class,
        'search' => ItemSearch::class,
        'sold_out' => WhereSoldOut::class,
        'day' => WhereCreatedBefore::class,
    ];

    protected array $sortable = [
        'name',
        'sku',
        'created_at',
        'updated_at',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function getQuantity(string|null $day): float
    {
        if ($day) {
            if (!Str::contains($day, ':')) {
                $day = Str::before($day, 'T') . 'T23:59:59';
            }
            return $this->deposits
                ->where('created_at', '<=', $day)
                ->sum('quantity');
        }
        return $this->quantity ?? 0;
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'option_items');
    }
}
