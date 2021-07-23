<?php

namespace App\Models;

use App\SearchTypes\ProductSearch;
use App\SearchTypes\WhereHasSlug;
use App\Traits\Sortable;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema ()
 *
 * @mixin IdeHelperProduct
 */
class Product extends Model
{
    use HasFactory, SoftDeletes, Searchable, Sortable;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * )
     *
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="Snake Ring",
     * )
     *
     * @OA\Property(
     *   property="slug",
     *   type="string",
     *   example="snake-ring",
     * )
     *
     * @OA\Property(
     *   property="price",
     *   type="number",
     *   example=229.99,
     * )
     *
     * @OA\Property(
     *   property="description_md",
     *   type="string",
     *   description="Description in MD.",
     *   example="# Awesome stuff!",
     * )
     *
     * @OA\Property(
     *   property="public",
     *   type="boolean",
     * )
     *
     * @OA\Property(
     *   property="visible",
     *   type="boolean",
     * )
     */

    protected $fillable = [
        'name',
        'slug',
        'price',
        'description_md',
        'public',
        'brand_id',
        'category_id',
        'quantity_step',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'public' => 'bool',
        'available' => 'bool',
        'quantity_step' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $searchable = [
        'name' => Like::class,
        'slug' => Like::class,
        'public',
        'brand' => WhereHasSlug::class,
        'category' => WhereHasSlug::class,
        'search' => ProductSearch::class,
    ];

    protected array $sortable = [
        'id',
        'price',
        'name',
        'created_at',
        'updated_at',
        'order',
    ];

    protected string $defaultSortBy = 'created_at';
    protected string $defaultSortDirection = 'desc';

    public function media(): BelongsToMany
    {
        return $this
            ->belongsToMany(Media::class, 'product_media')
            ->orderByPivot('order');
    }

    /**
     * @OA\Property(
     *   property="set",
     *   ref="#/components/schemas/ProductSet",
     * )
     */
    public function sets(): BelongsToMany
    {
        return $this->belongsToMany(ProductSet::class, 'product_set_product');
    }

    /**
     * @OA\Property(
     *   property="brand",
     *   ref="#/components/schemas/ProductSet",
     * )
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductSet::class, 'brand_id');
    }

    /**
     * @OA\Property(
     *   property="category",
     *   ref="#/components/schemas/ProductSet",
     * )
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductSet::class, 'category_id');
    }

    /**
     * @OA\Property(
     *   property="schemas",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/Schema"),
     * )
     */
    public function schemas(): BelongsToMany
    {
        return $this
            ->belongsToMany(Schema::class, 'product_schemas')
            ->orderByPivot('order');
    }

    public function orders(): BelongsToMany
    {
        return $this
            ->belongsToMany(Order::class)
            ->using(OrderProduct::class);
    }

    /**
     * @OA\Property(
     *   property="tags",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/Tag"),
     * )
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    /**
     * @OA\Property(
     *   property="description_html",
     *   type="string",
     *   example="<h1>Awesome stuff!</h1>",
     *   description="Description in HTML.",
     * )
     *
     * @var string
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return parsedown(strip_tags($this->description_md));
    }

    /**
     * @OA\Property(
     *   property="available",
     *   type="boolean",
     *   description="Whether product is available.",
     * )
     */
    public function getAvailableAttribute(): bool
    {
        if ($this->schemas()->count() <= 0) {
            return true;
        }

        // a product is available if all required schematics are available
        foreach ($this->schemas as $schema) {
            if ($schema->required && !$schema->available) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        $isBrandPublic = $this->brand ? 
            $this->brand->public && $this->brand->public_parent : true;

        $isCategoryPublic = $this->category ?
            $this->category->public && $this->category->public_parent : true;

        $isAnySetPublic = $this->sets()->count() > 0 ?
            $this->sets()->where('public', true)->where('public_parent', true) : true;

        return $this->public && $isBrandPublic && $isCategoryPublic && $isAnySetPublic;
    }

    public function scopePublic($query)
    {
        $query->where('public', true);

        $query
        ->where('public', true)
        ->where(function (Builder $query) {
            $query
                ->whereDoesntHave('brand')
                ->orWhereHas('brand',
                    fn (Builder $builder) => $builder->where('public', true)->where('public_parent', true),
                );
        })
        ->where(function (Builder $query) {
            $query
                ->whereDoesntHave('category')
                ->orWhereHas('category',
                    fn (Builder $builder) => $builder->where('public', true)->where('public_parent', true),
                );
        })
        ->where(function (Builder $query) {
            $query
                ->whereDoesntHave('sets')
                ->orWhereHas('sets',
                    fn (Builder $builder) => $builder->where('public', true)->where('public_parent', true),
                );
        });

        return $query;
    }
}
