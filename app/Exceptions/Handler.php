<?php

namespace App\Exceptions;

use App\Http\Resources\ErrorResource;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Config;
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
        MethodNotAllowedHttpException::class => [
            'message' => 'Page not found',
            'code' => Response::HTTP_NOT_FOUND,
        ],
        ValidationException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        StoreException::class => [
            'code' => Response::HTTP_BAD_REQUEST,
        ],
        AppAccessException::class => [
            'code' => Response::HTTP_BAD_REQUEST,
        ],
        AuthException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        MediaException::class => [
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ],
        AppException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        OrderException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        RoleException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        AuthorizationException::class => [
            'message' => 'Unauthorized',
            'code' => Response::HTTP_FORBIDDEN,
        ],
        WebHookCreatorException::class => [
            'code' => Response::HTTP_FORBIDDEN,
        ],
        WebHookEventException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        TokenExpiredException::class => [
            'message' => 'Unauthorized',
            'code' => Response::HTTP_UNAUTHORIZED,
        ],
        PackageException::class => [
            'code' => Response::HTTP_BAD_GATEWAY,
        ],
        PackageAuthException::class => [
            'code' => Response::HTTP_BAD_GATEWAY,
        ],
        ItemException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        TFAException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        MediaCriticalException::class => [
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ],
        DiscountException::class => [
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        GoogleProductCategoryFileException::class => [
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ],
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<string>
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
                self::ERRORS[$class]['message'] ?? $exception->getMessage(),
                self::ERRORS[$class]['code'] ?? 500,
                method_exists($exception, 'errors') ? $exception->errors() : [],
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
}
