<?php

namespace App\Http\Controllers;

use App\Dtos\AuthProviderDto;
use App\Dtos\AuthProviderLoginDto;
use App\Dtos\AuthProviderMergeAccountDto;
use App\Enums\AuthProviderKey;
use App\Http\Requests\AuthProviderIndexRequest;
use App\Http\Requests\AuthProviderLoginRequest;
use App\Http\Requests\AuthProviderMergeAccountRequest;
use App\Http\Requests\AuthProviderRedirectRequest;
use App\Http\Requests\AuthProviderUpdateRequest;
use App\Http\Resources\AuthProviderRedirectResource;
use App\Http\Resources\AuthProviderResource;
use App\Http\Resources\AuthResource;
use App\Models\AuthProvider;
use App\Services\Contracts\ProviderServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;
use Laravel\Socialite\Facades\Socialite;

class ProviderController extends Controller
{
    public function __construct(
        private ProviderServiceContract $providerService,
    ) {
    }

    public function getProvidersList(AuthProviderIndexRequest $request): JsonResource
    {
        $active = $request->has('active') ? $request->boolean('active') : null;
        return $this->providerService->getProvidersList($active);
    }

    public function getProvider(string $authProviderKey): JsonResource
    {
        $provider = $this->providerService->getProvider($authProviderKey);

        return AuthProviderResource::make($provider);
    }

    public function update(AuthProviderUpdateRequest $request, string $authProviderKey): JsonResponse
    {
        $dto = AuthProviderDto::instantiateFromRequest($request);

        /** @var AuthProvider $provider */
        $provider = AuthProvider::query()->where('key', $authProviderKey)->first();

        return Response::json($this->providerService->update($dto, $provider));
    }

    public function login(AuthProviderLoginRequest $request, string $authProviderKey): JsonResource
    {
        $data = $this->providerService->login($authProviderKey, AuthProviderLoginDto::instantiateFromRequest($request));

        return AuthResource::make($data);
    }

    public function redirect(AuthProviderRedirectRequest $request, string $authProviderKey): JsonResource
    {
        $this->providerService->setupRedirect(
            $authProviderKey,
            $request->input('return_url'),
        );

        $driver = AuthProviderKey::getDriver($authProviderKey);

        return AuthProviderRedirectResource::make([
            'redirect_url' => Socialite::driver($driver)
                ->stateless()
                ->redirect()
                ->getTargetUrl(),
        ]);
    }

    public function mergeAccount(AuthProviderMergeAccountRequest $request): JsonResponse
    {
        $this->providerService->mergeAccount(AuthProviderMergeAccountDto::instantiateFromRequest($request));

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
