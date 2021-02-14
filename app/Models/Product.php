<?php

namespace App\Models;

use App\Schemas\Schema;
use App\SearchTypes\ProductSearch;
use App\SearchTypes\WhereHasSlug;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Schema()
 */
class Product extends Model
{
    use SoftDeletes, Searchable, HasFactory;

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
        'digital',
        'public',
        'brand_id',
        'category_id',
        'original_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'public' => 'bool',
        'digital' => 'bool',
    ];

    protected array $searchable = [
        'name' => Like::class,
        'slug' => Like::class,
        'public',
        'brand' => WhereHasSlug::class,
        'category' => WhereHasSlug::class,
        'search' => ProductSearch::class,
    ];

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'product_media');
    }

    /**
     * @OA\Property(
     *   property="brand",
     *   ref="#/components/schemas/Brand",
     * )
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @OA\Property(
     *   property="category",
     *   ref="#/components/schemas/Category",
     * )
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @OA\Property(
     *   property="schemas",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/Schema"),
     * )
     */
    public function schemas(): Builder
    {
        return DB::table('product_schemas')->where('product_id', $this->getKey());
    }

    public function getSchemasAttribute(): Collection
    {
        return $this->schemas()->get()->map(function ($schema): Schema {
            return ($schema->schema_type)::findOrFail($schema->schema_id);
        });
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class)->using(OrderItem::class);
    }

    /**
     * Description in HTML.
     *
     * @OA\Property(
     *   property="description_html",
     *   type="string",
     *   example="<h1>Awesome stuff!</h1>",
     * )
     *
     * @var string
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return parsedown(strip_tags($this->description_md));
    }

    /**
     * Whether product is available.
     *
     * @OA\Property(
     *   property="available",
     *   type="boolean",
     * )
     *
     * @var bool
     */
    public function getAvailableAttribute(): bool
    {
        return true; // temp

//        return $this->schemas()->exists() ? $this->schemas()->first()
//            ->schemaItems()->first()->item->quantity > 0 : false;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->public && $this->brand->public && $this->category->public;
    }
}
