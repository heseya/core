<?php

declare(strict_types=1);

namespace Domain\Manufacturer\Models;

use App\Models\Address;
use App\Models\Model;
use App\Models\Product;
use Domain\Manufacturer\Criteria\ManufacturerSearch;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

final class Manufacturer extends Model
{
    use HasCriteria;
    use HasFactory;

    /** @var string[] */
    protected $fillable = [
        'id',
        'name',
        'first_name',
        'last_name',
        'email',
        'address_id',
    ];

    /** @var string[] */
    protected array $criteria = [
        'search' => ManufacturerSearch::class,
    ];

    /**
     * @return BelongsTo<Address, self>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    /**
     * @return HasMany<Product>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return Collection<int, string>
     */
    public function productIds(): Collection
    {
        return $this->products()->pluck('products.id');
    }
}
