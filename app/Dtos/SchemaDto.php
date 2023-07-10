<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

final class SchemaDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    public array $translations;

    protected Missing|string $type;
    protected float|Missing|null $price;
    protected bool|Missing $hidden;
    protected bool|Missing $required;
    protected float|Missing|null $min;
    protected float|Missing|null $max;
    protected float|Missing|null $step;
    protected Missing|string|null $default;
    protected Missing|string|null $pattern;
    protected Missing|string|null $validation;
    protected array|Missing|null $used_schemas;
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
            translations: $request->input('translations', []),
            type: $request->input('type', new Missing()),
            price: $request->input('price', new Missing()),
            hidden: $request->has('hidden') ? $request->boolean('hidden') : new Missing(),
            required: $request->has('required') ? $request->boolean('required') : new Missing(),
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

    public function getUsedSchemas(): array|Missing|null
    {
        return $this->used_schemas;
    }

    public function getOptions(): array|Missing
    {
        return $this->options;
    }
}
