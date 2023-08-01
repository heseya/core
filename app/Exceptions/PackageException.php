<?php

namespace App\Exceptions;

use Illuminate\Support\Str;
use Throwable;

class PackageException extends StoreException
{
    private mixed $errors;

    public function __construct(
        string $message = '',
        int $code = 0,
        mixed $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function errors(): array
    {
        if (is_array($this->errors)) {
            $response = [];
            foreach ($this->errors as $key) {
                /** @var string $field */
                $field = Str::replace('/', '.', $key->field);
                $response[$field] = [$key->message . ' [' . $field . ']'];
            }

            return $response;
        }
        /** @var string $field */
        $field = Str::replace('/', '.', $this->errors->field);

        return [$field => [$this->errors->message . ' [' . $field . ']']];
    }
}
