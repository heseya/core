<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class Handler extends ExceptionHandler
{
    private array $errors = [
        AuthenticationException::class => [
            'message' => 'Unauthorized',
            'code' => 401,
        ],
        NotFoundHttpException::class => [
            'message' => 'Page not found',
            'code' => 404,
        ],
        ValidationException::class => [
            'code' => 422,
        ],
        StoreException::class => [
            'code' => 400,
        ],
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     */
    public function report(Throwable $e): void
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        $class = get_class($exception);

        if (isset($this->errors[$class])) {
            $error = new Error(
                $this->errors[$class]['message'] ?? $exception->getMessage(),
                $this->errors[$class]['code'] ?? 500,
            );
        } else {
            if (App::environment('local')) {
                return parent::render($request, $exception);
            }
            $error = new Error;
        }

        if (app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        return ErrorResource::make($error)
            ->response()
            ->setStatusCode($error->code);
    }
}
