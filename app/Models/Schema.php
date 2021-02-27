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
 * @OA\Schema()
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
     * @OA\Property(
     *   property="type",
     *   type="string",
     *   enum={"string", "numeric", "boolean", "date", "select", "file"},
     * )
     */
    public const TYPES = [
        0 => 'string',
        1 => 'numeric',
        2 => 'boolean',
        3 => 'date',
        4 => 'select',
        5 => 'file',
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
     *   description="Additional price the customer will have to pay after selecting the option (can be negative)",
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
        if ($this->type != 4) {
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
            $validation->push('max:' . $this->min);
        }

        if ($this->type === 4) {
            $validation->push('uuid');
            $validation->push(new OptionAvailable($this, $quantity));
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
        return $this->hasMany(Option::class)->orderBy('name');
    }
}
