<?php

namespace App\Services\Contracts;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\PasswordResetSaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

interface AuthServiceContract
{
    public function login(LoginRequest $request): JsonResource;

    public function logout(Request $request): JsonResponse;

    public function resetPassword(PasswordResetRequest $request): JsonResponse;

    public function showResetPasswordForm(Request $request): JsonResource;

    public function saveResetPassword(PasswordResetSaveRequest $request): JsonResponse;

    public function changePassword(PasswordChangeRequest $request): JsonResponse;

    public function loginHistory(Request $request): JsonResource;
}
