<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema()
 */
class Schema extends Model
{
    use HasFactory;

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

    public function getAvailableAttribute(): bool
    {
        if ($this->disabled) {
            return false;
        }

        if ($this->options()->count() <= 0) {
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
     *
     * @return bool
     */
    public function validate($input): bool
    {
        $validation = explode('|', $this->validation ?? '');

        $validation[] = $this->type;

        if ($this->required) {
            $validation[] = 'required';
        }

        if ($this->max) {
            $validation[] = 'max:' . $this->max;
        }

        if ($this->min) {
            $validation[] = 'max:' . $this->min;
        }

        dd($validation);

        $validator = Validator::make($input, $validation);

        if ($validator->fails()) {
            return false;
        }

        return true;
    }

    public function setTypeAttribute($value)
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
        return $this->hasMany(Option::class);
    }
}
