<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\SchemaSearch;
use App\Enums\SchemaType;
use App\Models\Contracts\SortableContract;
use App\Rules\OptionAvailable;
use App\Traits\HasMetadata;
use App\Traits\Sortable;
use BenSampo\Enum\Exceptions\InvalidEnumKeyException;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @mixin IdeHelperSchema
 */
class Schema extends Model implements SortableContract
{
    use HasFactory, HasCriteria, Sortable, HasMetadata;

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
        'available',
        'shipping_time',
        'shipping_date',
    ];

    protected $casts = [
        'price' => 'float',
        'hidden' => 'bool',
        'required' => 'bool',
        'available' => 'bool',
        'type' => SchemaType::class,
    ];

    protected array $criteria = [
        'search' => SchemaSearch::class,
        'name' => Like::class,
        'hidden',
        'required',
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
    ];

    protected array $sortable = [
        'name',
        'sku',
        'created_at',
        'updated_at',
    ];

    /**
     * Check if user input is valid
     */
    public function validate(mixed $value): void
    {
        $validation = new Collection();

        if ($this->required) {
            $validation->push('required');
        } elseif ($value === null) {
            return;
        }

        if ($this->max) {
            $validation->push('max:' . $this->max);
        }

        if ($this->min) {
            $validation->push('min:' . $this->min);
        }

        if ($this->type->is(SchemaType::SELECT)) {
            $validation->push('uuid');
            $validation->push(new OptionAvailable($this));
        }

        if (
            $this->type->is(SchemaType::NUMERIC) ||
            $this->type->is(SchemaType::MULTIPLY) ||
            $this->type->is(SchemaType::MULTIPLY_SCHEMA)
        ) {
            $validation->push('numeric');
        }

        $validationStrings = [
            'attribute' => $this->name,
            'min' => $this->min,
            'max' => $this->max,
        ];

        $validator = Validator::make(
            [$this->getKey() => $value],
            [$this->getKey() => $validation],
            [
                'required' => Lang::get('validation.schema.required', $validationStrings),
                'numeric' => Lang::get('validation.schema.numeric', $validationStrings),
                'uuid' => Lang::get('validation.schema.uuid', $validationStrings),
                'min' => Lang::get('validation.schema.min', $validationStrings),
                'max' => Lang::get('validation.schema.max', $validationStrings),
            ],
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function getItems(int|string|null $value, float $quantity = 0): array
    {
        $items = [];

        if ($value === null || !$this->type->is(SchemaType::SELECT)) {
            return $items;
        }

        $option = $this->options()->find($value);

        foreach ($option?->items as $item) {
            $items[$item->getKey()] = $quantity;
        }

        return $items;
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class)
            ->with(['items', 'metadata', 'metadataPrivate'])
            ->orderBy('order')
            ->orderBy('created_at')
            ->orderBy('name', 'DESC');
    }

    /**
     * @throws InvalidEnumKeyException
     */
    public function setTypeAttribute(mixed $value): void
    {
        if (!is_integer($value)) {
            $value = SchemaType::fromKey(Str::upper($value));
        }

        $this->attributes['type'] = $value;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_schemas');
    }

    /**
     * @template TMakeKey of array-key
     * @template TMakeValue
     *
     * @param mixed $value
     * @param Arrayable<TMakeKey, TMakeValue>|iterable<TMakeKey, TMakeValue>|null $schemas
     */
    public function getPrice(mixed $value, $schemas): float
    {
        $schemaKeys = Collection::make($schemas)->keys();

        if ($this->usedBySchemas()->whereIn($this->getKeyName(), $schemaKeys)->exists()) {
            return 0.0;
        }

        return $this->getUsedPrice($value, $schemas);
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

    public function usedSchemas(): BelongsToMany
    {
        return $this->belongsToMany(
            Schema::class,
            'schema_used_schemas',
            'schema_id',
            'used_schema_id',
        );
    }

    /**
     * @template TMakeKey of array-key
     * @template TMakeValue
     *
     * @param mixed $value
     * @param Arrayable<TMakeKey, TMakeValue>|iterable<TMakeKey, TMakeValue>|null $schemas
     */
    private function getUsedPrice(mixed $value, $schemas): float
    {
        $price = $this->price;

        if (!$this->required && $value === null) {
            return 0;
        }

        if (
            ($this->type->is(SchemaType::STRING) || $this->type->is(SchemaType::NUMERIC)) &&
            Str::length(trim($value)) === 0
        ) {
            return 0;
        }

        if ($this->type->is(SchemaType::BOOLEAN) && ((bool) $value) === false) {
            return 0;
        }

        if ($this->type->is(SchemaType::SELECT)) {
            $option = $this->options()->findOrFail($value);

            $price += $option->price;
        }

        if ($this->type->is(SchemaType::MULTIPLY)) {
            $price *= (float) $value;
        }

        if ($this->type->is(SchemaType::MULTIPLY_SCHEMA)) {
            $usedSchema = $this->usedSchemas()->firstOrFail();
            $price = $value * $usedSchema->getUsedPrice($schemas[$usedSchema->getKey()], $schemas);
        }

        return $price;
    }
}
