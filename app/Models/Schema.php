<?php

namespace App\Models;

use App\Criteria\MetadataPrivateSearch;
use App\Criteria\MetadataSearch;
use App\Criteria\SchemaSearch;
use App\Criteria\WhereInIds;
use App\Enums\SchemaType;
use App\Models\Contracts\SortableContract;
use App\Rules\OptionAvailable;
use App\Traits\HasMetadata;
use App\Traits\Sortable;
use BenSampo\Enum\Exceptions\InvalidEnumKeyException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Heseya\Searchable\Criteria\Like;
use Heseya\Searchable\Traits\HasCriteria;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @property SchemaType $type;
 *
 * @mixin IdeHelperSchema
 */
class Schema extends Model implements SortableContract
{
    use HasFactory;
    use HasCriteria;
    use Sortable;
    use HasMetadata;

    protected $fillable = [
        'type',
        'name',
        'description',
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

        //        'price',
    ];

    protected $casts = [
        'hidden' => 'bool',
        'required' => 'bool',
        'available' => 'bool',
        'type' => SchemaType::class,

        //        'price' => 'float',
    ];

    protected array $criteria = [
        'search' => SchemaSearch::class,
        'name' => Like::class,
        'hidden',
        'required',
        'metadata' => MetadataSearch::class,
        'metadata_private' => MetadataPrivateSearch::class,
        'ids' => WhereInIds::class,
    ];

    protected array $sortable = [
        'name',
        'sku',
        'created_at',
        'updated_at',
    ];

    /**
     * Check if user input is valid.
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

        if ($option?->items) {
            foreach ($option->items as $item) {
                $items[$item->getKey()] = $quantity;
            }
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
     * @throws RoundingNecessaryException
     * @throws MoneyMismatchException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function getPrice(mixed $value, array $schemas): Money
    {
        $schemaKeys = Collection::make($schemas)->keys();
        $currency = 'PLN';

        if ($this->usedBySchemas()->whereIn($this->getKeyName(), $schemaKeys)->exists()) {
            return Money::of(0, $currency);
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
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws UnknownCurrencyException
     */
    private function getUsedPrice(mixed $value, array $schemas): Money
    {
        $price = $this->price->value;
        $currency = 'PLN';

        if (!$this->required && $value === null) {
            return Money::of(0, $currency);
        }

        if (
            ($this->type->is(SchemaType::STRING) || $this->type->is(SchemaType::NUMERIC)) &&
            Str::length(trim($value)) === 0
        ) {
            return Money::of(0, $currency);
        }

        if ($this->type->is(SchemaType::BOOLEAN) && ((bool) $value) === false) {
            return Money::of(0, $currency);
        }

        if ($this->type->is(SchemaType::SELECT)) {
            /** @var Option $option */
            $option = $this->options()->findOrFail($value);

            $price = $price->plus($option->price->value);
        }

        if ($this->type->is(SchemaType::MULTIPLY)) {
            $price = $price->multipliedBy((float) $value, roundingMode: RoundingMode::HALF_UP);
        }

        if ($this->type->is(SchemaType::MULTIPLY_SCHEMA)) {
            /** @var Schema $usedSchema */
            $usedSchema = $this->usedSchemas()->firstOrFail();

            $price = $usedSchema
                ->getUsedPrice($schemas[$usedSchema->getKey()], $schemas)
                ->multipliedBy(
                    (float) $value,
                    roundingMode: RoundingMode::HALF_UP,
                );
        }

        return $price;
    }

    public function price(): MorphOneWithIdentifier
    {
        return $this->morphOneWithIdentifier(
            Price::class,
            'model',
            'price_type',
            'price',
        );
    }
}
