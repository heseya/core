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
    protected string|null|Missing $description;
    protected float|null|Missing $price;
    protected bool|Missing $hidden;
    protected bool|Missing $required;
    protected float|null|Missing $min;
    protected float|null|Missing $max;
    protected float|null|Missing $step;
    protected string|null|Missing $default;
    protected string|null|Missing $pattern;
    protected string|null|Missing $validation;
    protected array|null|Missing $used_schemas;
    protected array|Missing $options;

    protected array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        $options = $request->input('options', new Missing());
        $optionsArrayDto = [];
        if (!$options instanceof Missing && $options !== null) {
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

    public function getUsedSchemas(): Missing|null|array
    {
        return $this->used_schemas;
    }

    public function getOptions(): Missing|array
    {
        return $this->options;
    }
}
