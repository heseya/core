<?php

namespace App\Services\Contracts;

use App\Dtos\AuthProviderDto;
use App\Dtos\AuthProviderLoginDto;
use App\Dtos\AuthProviderMergeAccountDto;
use App\Models\AuthProvider;
use Illuminate\Http\Resources\Json\JsonResource;

interface ProviderServiceContract
{
    public function getProvidersList(bool|null $active): JsonResource;
    public function getProvider(string $authProviderKey): ?AuthProvider;
    public function update(AuthProviderDto $dto, AuthProvider $provider): AuthProvider;
    public function setupRedirect(string $authProviderKey, string $returnUrl): void;
    public function login(string $authProviderKey, AuthProviderLoginDto $dto): array;
    public function mergeAccount(AuthProviderMergeAccountDto $dto): void;
}
