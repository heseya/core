<?php

namespace App\Rules;

use App\Models\Media;
use Illuminate\Contracts\Validation\Rule;

class MediaSlug implements Rule
{
    protected string $error;

    public function __construct(
        private Media $media,
    ) {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function passes($attribute, $value): bool
    {
        return (is_null($this->media->slug) && is_null($value)) || !is_null($value);
    }

    public function message(): string
    {
        return 'Media slug cannot be removed once is set.';
    }
}
