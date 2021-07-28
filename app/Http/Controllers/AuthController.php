<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\AuthControllerSwagger;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\PasswordResetSaveRequest;
use App\Http\Resources\AuthResource;
use App\Http\Resources\LoginHistoryResource;
use App\Http\Resources\UserResource;
use App\Services\Contracts\AuthServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class AuthController extends Controller implements AuthControllerSwagger
{
    private AuthServiceContract $authServiceContract;

    public function __construct(AuthServiceContract $authServiceContract)
    {
        $this->authServiceContract = $authServiceContract;
    }

    public function login(LoginRequest $request): JsonResource
    {
        $token = $this->authServiceContract->login(
            $request->input('email'),
            $request->input('password'),
            $request->ip(),
            $request->userAgent()
        );

        return AuthResource::make($token);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authServiceContract->logout($request->user());

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        $this->authServiceContract->resetPassword($request->input('email'));

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function showResetPasswordForm(Request $request): JsonResource
    {
        $user = $this->authServiceContract->showResetPasswordForm(
            $request->input('email'),
            $request->input('token')
        );

        return UserResource::make($user);
    }

    public function saveResetPassword(PasswordResetSaveRequest $request): JsonResponse
    {
        $this->authServiceContract->saveResetPassword(
            $request->input('email'),
            $request->input('token'),
            $request->input('password')
        );

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function changePassword(PasswordChangeRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authServiceContract->changePassword(
            $user,
            $request->input('password'),
            $request->input('password_new')
        );

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function loginHistory(Request $request): JsonResource
    {
        $session = $this->authServiceContract->loginHistory($request->user());

        return LoginHistoryResource::collection(
            $session->paginate(12),
        );
    }

    public function killUserSession(Request $request): JsonResource
    {
        $session = $this->authServiceContract->killUserSession($request->user());

        return LoginHistoryResource::collection(
            $session->paginate(12),
        );
    }

    public function killAllOldUserSessions(Request $request): JsonResource
    {
        $session = $this->authServiceContract->killAllOldUserSessions($request->user());

        return LoginHistoryResource::collection($session);
    }
}
