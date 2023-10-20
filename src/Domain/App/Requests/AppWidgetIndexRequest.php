<?php

declare(strict_types=1);

namespace Domain\App\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AppWidgetIndexRequest extends FormRequest
{
    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'section' => ['string'],
        ];
    }
}
