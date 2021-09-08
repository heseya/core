<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class Handler extends ExceptionHandler
{
    private const ERRORS = [
        AuthenticationException::class => [
            'message' => 'Unauthenticated',
            'code' => Response::HTTP_UNAUTHORIZED,
        ],
        AccessDeniedHttpException::class => [
            'message' => 'Unauthorized',
            'code' => Response::HTTP_FORBIDDEN,
        ],
        NotFoundHttpException::class => [
            'message' => 'Page not found',
            'code' => Response::HTTP_NOT_FOUND,
        ],
        ModelNotFoundException::class => [
            'message' => 'Page not found',
            'code' => Response::HTTP_NOT_FOUND,
        ],
        ValidationException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        StoreException::class => [
            'code' => Response::HTTP_BAD_REQUEST,
        ],
        AuthException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        MediaException::class => [
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ],
        OrderException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        RoleException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
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
        'token',
    ];

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception): Response
    {
        $class = $exception::class;

        if (array_key_exists($class, self::ERRORS)) {
            $error = new Error(
                self::ERRORS[$class]['message'] ?? $exception->getMessage(),
                self::ERRORS[$class]['code'] ?? 500,
                method_exists($exception, 'errors') ? $exception->errors() : [],
            );
        } else {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($exception);
            }

            if (config('app.debug') === true) {
                return parent::render($request, $exception);
            }

            $error = new Error();
        }

        return ErrorResource::make($error)
            ->response()
            ->setStatusCode($error->code);
    }
}
