<?php

namespace App\Services\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

interface AuthServiceContract
{
    public function login(Request $request): JsonResource;

    public function logout(Request $request): JsonResponse;

    public function resetPassword(Request $request): JsonResponse;

    public function showResetPasswordForm(Request $request): JsonResource;

    public function saveResetPassword(Request $request): JsonResponse;

    public function changePassword(Request $request): JsonResponse;

    public function loginHistory(Request $request): JsonResource;
}
