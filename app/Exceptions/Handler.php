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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class Handler extends ExceptionHandler
{
    private const ERRORS = [
        AuthenticationException::class => ErrorCode::UNAUTHORIZED,
        AccessDeniedHttpException::class => ErrorCode::FORBIDDEN,
        NotFoundHttpException::class => ErrorCode::NOT_FOUND,
        ModelNotFoundException::class => ErrorCode::NOT_FOUND,
        MethodNotAllowedHttpException::class => ErrorCode::NOT_FOUND,
        ValidationException::class => ErrorCode::VALIDATION_ERROR,
        StoreException::class => ErrorCode::BAD_REQUEST,
        AppAccessException::class => ErrorCode::BAD_REQUEST,
        AuthException::class => ErrorCode::UNPROCESSABLE_ENTITY,
        MediaException::class => ErrorCode::INTERNAL_SERVER_ERROR,
        AppException::class => ErrorCode::UNPROCESSABLE_ENTITY,
        OrderException::class => ErrorCode::UNPROCESSABLE_ENTITY,
        RoleException::class => ErrorCode::UNPROCESSABLE_ENTITY,
        AuthorizationException::class => ErrorCode::FORBIDDEN,
        WebHookCreatorException::class => ErrorCode::FORBIDDEN,
        WebHookEventException::class => ErrorCode::UNPROCESSABLE_ENTITY,
        TokenExpiredException::class => ErrorCode::UNAUTHORIZED,
        PackageException::class => ErrorCode::BAD_GATEWAY,
        PackageAuthException::class => ErrorCode::BAD_GATEWAY,
        ItemException::class => ErrorCode::UNPROCESSABLE_ENTITY,
        TFAException::class => ErrorCode::UNPROCESSABLE_ENTITY,
        MediaCriticalException::class => ErrorCode::INTERNAL_SERVER_ERROR,
        DiscountException::class => ErrorCode::UNPROCESSABLE_ENTITY,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
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
                self::ERRORS[$class],
                ErrorCode::getCode(self::ERRORS[$class]),
                ErrorCode::fromValue(self::ERRORS[$class])->key,
                method_exists($exception, 'errors') ?
                    $this->mapValidationErrors($exception->validator) : [],
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

                $attribute = Str::of($attribute)->afterLast("\\")->toString();
                $key = ValidationError::coerce($attribute)->value ?? 'INTERNAL_SERVER_ERROR';

                $message = array_key_exists($field, $errors) ?
                    array_key_exists($index, $errors[$field]) ?
                        $errors[$field][$index] : null
                    : null;

                #Workaround for Password::defaults() rule
                if ($key === ValidationError::PASSWORD) {
                    $key = Str::contains($message, 'data leak') ?
                        ValidationError::PASSWORDCOMPROMISED : ValidationError::PASSWORDLENGTH;
                }

                $validationErrors[$field][$index] = [
                    'key' => $key
                ] + $this->createValidationAttributeData($key, $attrValue);

                if ($message !== null) {
                    $validationErrors[$field][$index] = $validationErrors[$field][$index] + [
                            'message' => $message
                        ];
                }

                $index++;
            }
        }

        return $validationErrors;
    }

    private function createValidationAttributeData(string $key, array $data): array
    {
        return match ($key) {
            ValidationError::MIN => [
                'min' => $data[0],
            ],
            ValidationError::MAX => [
                'max' => $data[0],
            ],
            ValidationError::SIZE => [
                'size' => $data[0],
            ],
            ValidationError::BETWEEN => [
                'min' => $data[0],
                'max' => $data[1],
            ],
            ValidationError::PASSWORDLENGTH => [
                'min' => Config::get('validation.min_length'),
            ],
            ValidationError::BEFOREOREQUAL,
            ValidationError::AFTEROREQUAL => [
                'when' => $data[0],
            ],
            ValidationError::PROHIBITEDUNLESS => [
                'field' => $data[0],
                'value' => $data[1],
            ],
            ValidationError::MIMETYPES => [
                'types' => $data,
            ],
            ValidationError::EXISTS => [
                'table' => $data[0],
                'field' => $data[1],
            ],
            ValidationError::GTE => [
                'field' => $data[0]
            ],
            default => []
        };
    }
}
