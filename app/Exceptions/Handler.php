<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use App\Enums\ValidationError;
use App\Http\Resources\ErrorResource;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class Handler extends ExceptionHandler
{
    private const ERRORS = [
        // 400
        AppAccessException::class => ErrorCode::BAD_REQUEST,
        StoreException::class => ErrorCode::BAD_REQUEST,

        // 401
        AccessDeniedHttpException::class => ErrorCode::UNAUTHORIZED,
        TokenExpiredException::class => ErrorCode::UNAUTHORIZED,

        // 403
        AuthenticationException::class => ErrorCode::FORBIDDEN,
        AuthorizationException::class => ErrorCode::FORBIDDEN,
        UnauthorizedException::class => ErrorCode::FORBIDDEN,

        // 404
        NotFoundHttpException::class => ErrorCode::NOT_FOUND,
        MethodNotAllowedHttpException::class => ErrorCode::NOT_FOUND,
        ModelNotFoundException::class => ErrorCode::NOT_FOUND,
        TokenInvalidException::class => ErrorCode::NOT_FOUND,

        // 422
        ValidationException::class => ErrorCode::VALIDATION_ERROR,
        ClientException::class => ErrorCode::UNPROCESSABLE_ENTITY,
        TFAException::class => ErrorCode::UNPROCESSABLE_ENTITY,
        GoogleProductCategoryFileException::class => ErrorCode::UNPROCESSABLE_ENTITY,
        OrderException::class => ErrorCode::UNPROCESSABLE_ENTITY,

        // 500
        ServerException::class => ErrorCode::INTERNAL_SERVER_ERROR,

        // 502
        PackageException::class => ErrorCode::BAD_GATEWAY,
        PackageAuthException::class => ErrorCode::BAD_GATEWAY,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
        'token',
    ];

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception): Response
    {
        $class = $exception::class;

        if (array_key_exists($class, self::ERRORS)) {
            if (method_exists($exception, 'isTypeSet') && $exception->isTypeSet()) {
                if (!($exception instanceof TFAException)) {
                    throw new Exception('$exception must be an instance of TFAException');
                }

                return TFAExceptionResource::make($exception->toArray())
                    ->response()
                    ->setStatusCode($exception->getCode());
            }

            $error = new Error(
                $exception->getMessage(),
                $exception instanceof StoreException ?
                    $exception->getCode() : ErrorCode::getCode(self::ERRORS[$class]),
                $exception instanceof StoreException ?
                    $exception->getKey() : self::ERRORS[$class]->name,
                $this->getExceptionData($exception),
            );
        } else {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($exception);
            }

            if (Config::get('app.debug') === true) {
                return parent::render($request, $exception);
            }

            $error = new Error();
        }

        if (Config::get('app.debug') === true) {
            $error->setStack($this->convertExceptionToArray($exception));
        }

        return ErrorResource::make($error)
            ->response()
            ->setStatusCode($error->code);
    }

    public function report(Throwable $e): void
    {
        $e instanceof StoreException && $e->isSimpleLogs() ? $e->logException() : parent::report($e);
    }

    private function mapValidationErrors(Validator $validator): array
    {
        $validationErrors = [];
        $errors = $validator->errors()->toArray();

        foreach ($validator->failed() as $field => &$value) {
            $value = array_change_key_case($value, CASE_UPPER);
            $index = 0;

            foreach ($value as $attribute => $attrValue) {
                $attribute = Str::of($attribute)->afterLast('\\')->toString();
                $key = ValidationError::getValue($attribute) ?? 'INTERNAL_SERVER_ERROR';

                $message = array_key_exists($field, $errors) ?
                    array_key_exists($index, $errors[$field]) ?
                        $errors[$field][$index] : null
                    : null;

                #Workaround for Password::defaults() rule
                if ($key === ValidationError::PASSWORD->value) {
                    $key = Str::contains($message, 'data leak') ?
                        ValidationError::PASSWORDCOMPROMISED : ValidationError::PASSWORDLENGTH;
                }

                $validationErrors[$field][$index] = [
                    'key' => $key,
                ] + $this->createValidationAttributeData($key, $attrValue);

                if ($message !== null) {
                    $validationErrors[$field][$index] += [
                        'message' => $message,
                    ];
                }

                $index++;
            }
        }

        return $validationErrors;
    }

    private function getExceptionData(Throwable|Exception $exception): array
    {
        if ($exception instanceof ServerException && Config::get('app.debug') === true) {
            return $exception->errors();
        }
        if ($exception instanceof StoreException) {
            return $exception->errors();
        }
        if ($exception instanceof ValidationException) {
            return $this->mapValidationErrors($exception->validator);
        }
        return [];
    }

    private function createValidationAttributeData(string $key, array $data): array
    {
        return match ($key) {
            ValidationError::MIN->name => [
                'min' => $data[0],
            ],
            ValidationError::MAX->name => [
                'max' => $data[0],
            ],
            ValidationError::SIZE->name => [
                'size' => $data[0],
            ],
            ValidationError::IN->name => [
                'available' => $data,
            ],
            ValidationError::BETWEEN->name => [
                'min' => $data[0],
                'max' => $data[1],
            ],
            ValidationError::PASSWORDLENGTH->name => [
                'min' => Config::get('validation.password_min_length'),
            ],
            ValidationError::BEFOREOREQUAL->name,
            ValidationError::AFTEROREQUAL->name => [
                'when' => $data[0],
            ],
            ValidationError::PROHIBITEDUNLESS->name => [
                'field' => $data[0],
                'value' => $data[1],
            ],
            ValidationError::MIMETYPES->name => [
                'types' => $data,
            ],
            ValidationError::EXISTS->name => [
                'table' => $data[0],
                'field' => $data[1],
            ],
            ValidationError::GTE->name => [
                'field' => $data[0],
            ],
            default => []
        };
    }
}
