<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\AuthControllerSwagger;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\PasswordResetSaveRequest;
use App\Http\Resources\AppResource;
use App\Http\Resources\AuthResource;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\UserResource;
use App\Models\App;
use App\Services\Contracts\AppServiceContract;
use App\Services\Contracts\AuthServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class AuthController extends Controller implements AuthControllerSwagger
{
    public function __construct(
        private AuthServiceContract $authService,
        private AppServiceContract $appService,
    ) {
    }

    public function login(LoginRequest $request): JsonResource
    {
        $tokens = $this->authService->login(
            $request->input('email'),
            $request->input('password'),
            $request->ip(),
            $request->userAgent()
        );

        return AuthResource::make($tokens);
    }

    public function refresh(Request $request): JsonResource
    {
        $tokens = $this->authService->refresh(
            $request->input('refresh_token'),
            $request->ip(),
            $request->userAgent(),
        );

        return AuthResource::make($tokens);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->input('email'));

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function showResetPasswordForm(Request $request): JsonResource
    {
        $user = $this->authService->showResetPasswordForm(
            $request->input('email'),
            $request->input('token')
        );

        return UserResource::make($user);
    }

    public function saveResetPassword(PasswordResetSaveRequest $request): JsonResponse
    {
        $this->authService->saveResetPassword(
            $request->input('email'),
            $request->input('token'),
            $request->input('password')
        );

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function changePassword(PasswordChangeRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authService->changePassword(
            $user,
            $request->input('password'),
            $request->input('password_new')
        );

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

//    public function loginHistory(Request $request): JsonResource
//    {
//        $session = $this->authServiceContract->loginHistory($request->user());
//
//        return LoginHistoryResource::collection(
//            $session->paginate(12),
//        );
//    }
//
//    public function killActiveSession(Request $request, string $oauthAccessTokensId): JsonResource
//    {
//        $session = $this->authServiceContract->killActiveSession($request->user(), $oauthAccessTokensId);
//
//        return LoginHistoryResource::collection(
//            $session->paginate(12),
//        );
//    }
//
//    public function killAllSessions(Request $request): JsonResource
//    {
//        $session = $this->authServiceContract->killAllSessions($request->user());
//
//        return LoginHistoryResource::collection($session);
//    }

    public function profile(Request $request): JsonResponse
    {
        $authenticable = $request->user();

        $resource = $authenticable instanceof App ? AppResource::make($authenticable)
            : UserResource::make($authenticable);

        return $resource
            ->response()
            ->setStatusCode(200);
    }

    public function identityProfile(string $identityToken, Request $request): JsonResource
    {
        $user = $this->authService->userByIdentity($identityToken);

        $prefix = $this->authService->isAppAuthenticated() ?
            $this->appService->appPermissionPrefix(Auth::user()) : null;

        return ProfileResource::make($user)->stripedPermissionPrefix($prefix);
    }
}
