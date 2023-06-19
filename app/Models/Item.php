<?php

namespace App\Models;

use App\Criteria\ItemSearch;
use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\WhereCreatedBefore;
use App\Criteria\WhereInIds;
use App\Criteria\WhereSoldOut;
use App\Models\Contracts\SortableContract;
use App\Traits\HasMetadata;
use App\Traits\Sortable;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property mixed $pivot
 *
 * @mixin IdeHelperItem
 */
class Item extends Model implements AuditableContract, SortableContract
{
    use SoftDeletes;
    use HasFactory;
    use HasCriteria;
    use Sortable;
    use Auditable;
    use HasMetadata;

    protected $fillable = [
        'id',
        'name',
        'sku',
        'quantity',
        'unlimited_stock_shipping_time',
        'unlimited_stock_shipping_date',
        'shipping_time',
        'shipping_date',
    ];

    protected array $criteria = [
        'name' => Like::class,
        'sku' => Like::class,
        'search' => ItemSearch::class,
        'sold_out' => WhereSoldOut::class,
        'day' => WhereCreatedBefore::class,
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
    ];

    protected array $sortable = [
        'name',
        'sku',
        'created_at',
        'updated_at',
        'quantity',
        'unlimited_stock_shipping_time',
        'unlimited_stock_shipping_date',
        'shipping_time',
        'shipping_date',
    ];

    protected $casts = [
        'quantity' => 'float',
        'shipping_date' => 'datetime',
        'unlimited_stock_shipping_date' => 'datetime',
    ];

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

    public function getQuantityRealAttribute(): float
    {
        return $this->deposits
            ->where(fn (Deposit $deposit) => $deposit->shipping_date === null || $deposit->shipping_date >= Carbon::now())
            ->where('from_unlimited', false)
            ->sum('quantity') ?? 0;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'option_items');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function groupedDeposits(): HasMany
    {
        return $this->deposits()
            ->selectRaw('item_id, SUM(quantity) as quantity, shipping_time, shipping_date, from_unlimited')
            ->groupBy(['shipping_time', 'shipping_date', 'item_id', 'from_unlimited'])
            ->having('quantity', '!=', '0')
            ->orderBy('shipping_time', 'desc')
            ->orderBy('shipping_date', 'desc')
            ->orderBy('from_unlimited', 'desc');
    }

    public function getSchemasAttribute(): Collection
    {
        $schemas = Collection::make();

        $this->options->each(fn (Option $option): Collection => $schemas->push($option->schema));

        return $schemas->unique();
    }
}
