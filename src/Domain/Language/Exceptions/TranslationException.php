<?php

declare(strict_types=1);

namespace Domain\Language\Exceptions;

use App\Exceptions\StoreException;
use App\Http\Resources\LanguageResource;
use App\Models\Model;
use Domain\Language\Language;
use Illuminate\Support\Collection;
use Throwable;

final class TranslationException extends StoreException
{
    public function __construct(
        string $message = 'No content in selected language',
        ?Throwable $previous = null,
        bool $simpleLogs = false,
        protected Model|null $model = null,
    ) {
        parent::__construct($message, $previous);
        $this->simpleLogs = $simpleLogs;
        $this->code = 406;
    }

    public function published(): Collection
    {
        $published = $this->model->published ?? [];

        return Language::whereIn('id', $published)->get();
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'published' => LanguageResource::collection($this->published()),
        ];
    }
}
