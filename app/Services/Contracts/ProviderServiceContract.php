<?php

namespace App\Services\Contracts;

use App\Dtos\AuthProviderDto;
use App\Models\AuthProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

interface ProviderServiceContract
{
    public function getProvidersList(bool $active): JsonResource;
    public function getProvider(string $authProviderKey): AuthProvider|array;
    public function update(AuthProviderDto $dto, AuthProvider $provider): AuthProvider;
    public function setupRedirect(string $authProviderKey, string $returnUrl): void;
    public function login(string $authProviderKey, Request $request): array;
}
