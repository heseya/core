<?php

namespace App\Models;

use App\Enums\SchemaType;
use App\Rules\OptionAvailable;
use App\SearchTypes\SchemaSearch;
use App\SearchTypes\TranslatedLike;
use App\SortColumnTypes\TranslatedColumn;
use BenSampo\Enum\Exceptions\InvalidEnumKeyException;
use Heseya\Searchable\Traits\Searchable;
use Heseya\Sortable\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Translatable\HasTranslations;

/**
 * @mixin IdeHelperSchema
 */
class Schema extends Model
{
    use HasFactory, Searchable, Sortable, HasTranslations;

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
        'published',
    ];

    protected $translatable = [
        'name',
        'description',
        'published',
    ];

    protected $casts = [
        'price' => 'float',
        'hidden' => 'bool',
        'required' => 'bool',
        'available' => 'bool',
        'type' => SchemaType::class,
        'published' => 'bool',
    ];

    protected $searchable = [
        'search' => SchemaSearch::class,
        'name' => TranslatedLike::class,
        'hidden',
        'required',
    ];

    protected array $sortable = [
        'name' => TranslatedColumn::class,
        'sku',
        'created_at',
        'updated_at',
    ];

    public function getAvailableAttribute(): bool
    {
        if (!$this->type->is(SchemaType::SELECT)) {
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
    public function validate($value, float $quantity = 0): void
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
            $validation->push(new OptionAvailable($this, $quantity));
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

    public function getItems($value, float $quantity = 0): array
    {
        $items = [];

        if ($value === null || !$this->type->is(SchemaType::SELECT)) {
            return $items;
        }

        $option = $this->options()->find($value);

        foreach ($option->items as $item) {
            $items[$item->getKey()] = $quantity;
        }

        return $items;
    }

    /**
     * @throws InvalidEnumKeyException
     */
    public function setTypeAttribute($value): void
    {
        if (!is_integer($value)) {
            $value = SchemaType::fromKey($value);
        }

        $this->attributes['type'] = $value;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_schemas');
    }

    public function getPrice($value, $schemas): float
    {
        $schemaKeys = collect($schemas)->keys();

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

    public function options(): HasMany
    {
        return $this->hasMany(Option::class)
            ->orderBy('order')
            ->orderBy('created_at')
            ->orderBy('name', 'DESC');
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

    private function getUsedPrice($value, $schemas): float
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
