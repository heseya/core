<?php

namespace App\Models;

use App\Rules\OptionAvailable;
use App\SearchTypes\SchemaSearch;
use App\Traits\Sortable;
use Heseya\Searchable\Searches\Like;
use Heseya\Searchable\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Schema(
 *   description="Schema allows a product to take on new optional characteristics that can be
 *   chosen by the userand influences the price based on said choices. Schemas can use other
 *   schemas for their price calculation e.g. multiply_schema multiplies price of different
 *   schema based on it's own value. SCHEMAS USED BY OTHERS SHOULD NOT AFFECT THE PRICE
 *   (schema multiplied by multiply_schema adds 0 to the price while multiply_schema adds
 *   the multiplied value)",
 * )
 * @mixin IdeHelperSchema
 */
class Schema extends Model
{
    use HasFactory, Searchable, Sortable;

    /**
     * @OA\Property(
     *   property="id",
     *   type="string",
     *   example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     * ),
     *
     * @OA\Property(
     *   property="type",
     *   type="string",
     *   description="multiply_schema(min, max, step) type uses one schema and multiplies
     *     it's price by own numeric value",
     *   enum={"string", "numeric", "boolean", "date", "select", "file", "multiply",
     *     "multiply_schema"},
     * )
     */
    public const TYPES = [
        0 => 'string',
        1 => 'numeric',
        2 => 'boolean',
        3 => 'date',
        4 => 'select',
        5 => 'file',
        6 => 'multiply',
        7 => 'multiply_schema',
    ];

    /**
     * @OA\Property(
     *   property="name",
     *   type="string",
     *   example="Size",
     * ),
     * @OA\Property(
     *   property="description",
     *   type="string",
     *   description="Short description, no html or md allowed",
     * ),
     * @OA\Property(
     *   property="price",
     *   type="float",
     *   description="Additional price the customer will have to pay after selecting the option
     *     (can be negative)",
     *   example=9.99,
     * ),
     * @OA\Property(
     *   property="hidden",
     *   type="boolean",
     *   example=false,
     * ),
     * @OA\Property(
     *   property="required",
     *   type="boolean",
     *   example=false,
     * ),
     * @OA\Property(
     *   property="min",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="max",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="step",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="default",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="pattern",
     *   type="string",
     * ),
     * @OA\Property(
     *   property="validation",
     *   type="string",
     * ),
     */
    protected $fillable = [
        'type',
        'name',
        'description',
        'price',
        'hidden',
        'required',
        'max',
        'min',
        'step',
        'default',
        'pattern',
        'validation',
    ];

    protected $casts = [
        'price' => 'float',
        'hidden' => 'bool',
        'required' => 'bool',
        'available' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchable = [
        'search' => SchemaSearch::class,
        'name' => Like::class,
        'hidden',
        'required',
    ];

    protected array $sortable = [
        'name',
        'sku',
        'created_at',
        'updated_at',
    ];

    public function getAvailableAttribute(): bool
    {
        if ($this->type !== 4) {
            return true;
        }

        // schema should be available if any of the options are available
        foreach ($this->options as $option) {
            if ($option->available) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user input is valid
     *
     * @param mixed $input
     * @param float $quantity
     *
     * @throws ValidationException
     */
    public function validate($input, float $quantity = 0): void
    {
        $validation = collect();

        if ($this->required) {
            $validation->push('required');
        }

        if ($this->max) {
            $validation->push('max:' . $this->max);
        }

        if ($this->min) {
            $validation->push('min:' . $this->min);
        }

        if ($this->type === 4) {
            $validation->push('uuid');
            $validation->push(new OptionAvailable($this, $quantity));
        }

        if ($this->type === 6) {
            $validation->push('numeric');
        }

        $validator = Validator::make(
            [$this->name => $input],
            [$this->name => $validation],
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function getTypeNameAttribute(): string
    {
        return Schema::TYPES[$this->type];
    }

    public function setTypeAttribute($value): void
    {
        if (!is_integer($value)) {
            $value = array_***REMOVED***(self::TYPES)[$value];
        }

        $this->attributes['type'] = $value;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_schemas');
    }

    /**
     * @OA\Property(
     *   property="options",
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/Option"),
     * )
     */
    public function options(): HasMany
    {
        return $this->hasMany(Option::class)
            ->orderBy('created_at')
            ->orderBy('name', 'DESC');
    }

    /**
     * @OA\Property(
     *   property="used_schemas",
     *   description="Array of schema id's given schema uses e.g.
     *   multiply_schema type uses one schema of which price it miltiplies",
     *   type="array",
     *   @OA\Items(
     *     type="string",
     *     example="used-schema-ids",
     *   ),
     * )
     */
    public function usedSchemas(): BelongsToMany
    {
        return $this->belongsToMany(
            Schema::class,
            'schema_used_schemas',
            'schema_id',
            'used_schema_id',
        );
    }

    public function usedBySchemas(): BelongsToMany
    {
        return $this->belongsToMany(
            Schema::class,
            'schema_used_schemas',
            'used_schema_id',
            'schema_id',
        );
    }

    public function getPrice($value, $schemas): float
    {
        $schemaKeys = collect($schemas)->keys();

        if ($this->usedBySchemas()->whereIn($this->getKeyName(), $schemaKeys)->exists()) {
            return 0.0;
        }

        return $this->getUsedPrice($value, $schemas);
    }

    private function getUsedPrice($value, $schemas): float
    {
        $price = $this->price;

        if ($this->type === 4) {
            $option = $this->options()->findOrFail($value);

            $price += $option->price;
        }

        if ($this->type === 6) {
            $price *= (float) $value;
        }

        if ($this->type === 7) {
            $usedSchema = $this->usedSchemas()->firstOrFail();
            $price = $value * $usedSchema->getUsedPrice($schemas[$usedSchema->getKey()], $schemas);
        }

        return $price;
    }
}
