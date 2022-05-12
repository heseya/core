<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class SchemaDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    protected string|Missing $type;
    protected string|Missing $name;
    protected string|Missing $description;
    protected float|Missing $price;
    protected bool|Missing $hidden;
    protected bool|Missing $required;
    protected float|Missing $min;
    protected float|Missing $max;
    protected float|Missing $step;
    protected string|Missing $default;
    protected string|Missing $pattern;
    protected string|Missing $validation;
    protected array|Missing $used_schemas;
    protected array|Missing $options;

    protected array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        $options = $request->input('options', new Missing());
        $optionsArrayDto = [];
        if (!$options instanceof Missing) {
            foreach ($options as $option) {
                $optionsArrayDto[] = OptionDto::fromArray($option);
            }
        }

        return new self(
            type: $request->input('type', new Missing()),
            name: $request->input('name', new Missing()),
            description: $request->input('description', new Missing()),
            price: $request->input('price', new Missing()),
            hidden: $request->input('hidden', new Missing()),
            required: $request->input('required', new Missing()),
            min: $request->input('min', new Missing()),
            max: $request->input('max', new Missing()),
            step: $request->input('step', new Missing()),
            default: $request->input('default', new Missing()),
            pattern: $request->input('pattern', new Missing()),
            validation: $request->input('validation', new Missing()),
            used_schemas: $request->input('used_schemas', new Missing()),
            options: $options instanceof Missing ? $options : $optionsArrayDto,
            metadata: self::mapMetadata($request),
        );
    }

    public function getUsedSchemas(): Missing|array
    {
        return $this->used_schemas;
    }

    public function getOptions(): Missing|array
    {
        return $this->options;
    }
}
