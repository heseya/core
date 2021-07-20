<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\AuthControllerSwagger;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\PasswordResetSaveRequest;
use App\Services\Contracts\AuthServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthController extends Controller implements AuthControllerSwagger
{
    private AuthServiceContract $authServiceContract;

    public function __construct(AuthServiceContract $authServiceContract)
    {
        $this->authServiceContract = $authServiceContract;
    }

    public function login(LoginRequest $request): JsonResource
    {
        return $this->authServiceContract->login($request);
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->authServiceContract->logout($request);
    }

    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        return $this->authServiceContract->resetPassword($request);
    }

    public function showResetPasswordForm(Request $request): JsonResource
    {
        return $this->authServiceContract->showResetPasswordForm($request);
    }

    public function saveResetPassword(PasswordResetSaveRequest $request): JsonResponse
    {
        return $this->authServiceContract->saveResetPassword($request);
    }

    public function changePassword(PasswordChangeRequest $request): JsonResponse
    {
        return $this->authServiceContract->changePassword($request);
    }

    public function loginHistory(Request $request): JsonResource
    {
        return $this->authServiceContract->loginHistory($request);
    }
}
