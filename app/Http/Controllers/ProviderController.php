<?php

namespace App\Http\Controllers;

use App\Dtos\AuthProviderDto;
use App\Enums\AuthProviderKey;
use App\Http\Requests\AuthProviderIndexRequest;
use App\Http\Requests\AuthProviderRedirectRequest;
use App\Http\Requests\AuthProviderUpdateRequest;
use App\Http\Resources\AuthResource;
use App\Models\AuthProvider;
use App\Services\Contracts\ProviderServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function getProvider(string $authProviderKey): JsonResponse
    {
        $provider = $this->providerService->getProvider($authProviderKey);

        return Response::json($provider);
    }

    public function update(AuthProviderUpdateRequest $request, string $authProviderKey): JsonResponse
    {
        $dto = AuthProviderDto::instantiateFromRequest($request);

        /** @var AuthProvider $provider */
        $provider = AuthProvider::query()->where('key', $authProviderKey)->first();

        return Response::json($this->providerService->update($dto, $provider));
    }

    public function login(Request $request, string $authProviderKey): JsonResource
    {
        $data = $this->providerService->login($authProviderKey, $request);

        return AuthResource::make($data);
    }

    public function redirect(AuthProviderRedirectRequest $request, string $authProviderKey): JsonResponse
    {
        $this->providerService->setupRedirect($authProviderKey, $request->input('return_url'));

        $driver = AuthProviderKey::getDriver($authProviderKey);

        return Response::json(
            Socialite::driver($driver)
                ->stateless()
                ->redirect()
                ->getTargetUrl()
        );
    }
}
