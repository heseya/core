<?php

declare(strict_types=1);

namespace Domain\User\Controllers;

use App\DTO\Auth\RegisterDto;
use App\Enums\SavedAddressType;
use App\Http\Controllers\Controller;
use App\Http\Resources\AppWithSavedAddressesResource;
use App\Http\Resources\AuthResource;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\SavedAddressResource;
use App\Http\Resources\TFARecoveryCodesResource;
use App\Http\Resources\TFAResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserWithSavedAddressesResource;
use App\Models\App;
use App\Models\SavedAddress;
use App\Models\User;
use App\Services\Contracts\AppServiceContract;
use Domain\User\Dtos\ChangePasswordDto;
use Domain\User\Dtos\LoginDto;
use Domain\User\Dtos\PasswordResetDto;
use Domain\User\Dtos\PasswordResetSaveDto;
use Domain\User\Dtos\ProfileUpdateDto;
use Domain\User\Dtos\SavedAddressStoreDto;
use Domain\User\Dtos\SavedAddressUpdateDto;
use Domain\User\Dtos\ShowResetPasswordFormDto;
use Domain\User\Dtos\TFAConfirmDto;
use Domain\User\Dtos\TFAPasswordDto;
use Domain\User\Dtos\TFASetupDto;
use Domain\User\Dtos\TokenRefreshDto;
use Domain\User\Services\Contracts\AuthServiceContract;
use Domain\User\Services\Contracts\SavedAddressServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceContract $authService,
        private readonly AppServiceContract $appService,
        private readonly SavedAddressServiceContract $savedAddersService,
    ) {}

    public function login(LoginDto $dto): JsonResource
    {
        $tokens = $this->authService->login($dto);

        return AuthResource::make($tokens);
    }

    public function refresh(TokenRefreshDto $dto): JsonResource
    {
        $tokens = $this->authService->refresh($dto);

        return AuthResource::make($tokens);
    }

    public function logout(): HttpResponse
    {
        $this->authService->logout();

        return Response::noContent();
    }

    public function resetPassword(PasswordResetDto $dto): HttpResponse
    {
        $this->authService->resetPassword($dto);

        return Response::noContent();
    }

    public function showResetPasswordForm(ShowResetPasswordFormDto $dto): JsonResource
    {
        $user = $this->authService->showResetPasswordForm($dto);

        return UserResource::make($user);
    }

    public function saveResetPassword(PasswordResetSaveDto $dto): HttpResponse
    {
        $this->authService->saveResetPassword($dto);

        return Response::noContent();
    }

    public function changePassword(ChangePasswordDto $dto): HttpResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $this->authService->changePassword($user, $dto);

        return Response::noContent();
    }

    public function profile(Request $request): JsonResponse
    {
        /** @var User|App|null $authenticable */
        $authenticable = $request->user();

        $resource = $authenticable instanceof App
            ? AppWithSavedAddressesResource::make($authenticable)
            : UserWithSavedAddressesResource::make($authenticable);

        return $resource
            ->response()
            ->setStatusCode(200);
    }

    public function checkIdentity(Request $request, ?string $identityToken = null): JsonResource
    {
        /** @var User|App|null $authenticable */
        $authenticable = $request->user();

        $user = $identityToken === null
            ? $this->authService->unauthenticatedUser()
            : $this->authService->userByIdentity($identityToken);

        $prefix = $authenticable instanceof App
            ? $this->appService->appPermissionPrefix($authenticable)
            : null;

        return ProfileResource::make($user)->stripedPermissionPrefix($prefix);
    }

    public function setupTFA(TFASetupDto $dto): JsonResource
    {
        return TFAResource::make($this->authService->setupTFA($dto));
    }

    public function confirmTFA(TFAConfirmDto $dto): JsonResource
    {
        return TFARecoveryCodesResource::make(
            $this->authService->confirmTFA($dto),
        );
    }

    public function generateRecoveryCodes(TFAPasswordDto $dto): JsonResource
    {
        return TFARecoveryCodesResource::make($this->authService->generateRecoveryCodes($dto));
    }

    public function removeTFA(TFAPasswordDto $dto): HttpResponse
    {
        $this->authService->removeTFA($dto);

        return Response::noContent();
    }

    public function removeUsersTFA(User $user): HttpResponse
    {
        Gate::inspect('removeUserTFA', [User::class, $user]);

        $this->authService->removeUsersTFA($user);

        return Response::noContent();
    }

    public function register(RegisterDto $dto): JsonResource
    {
        return UserResource::make($this->authService->register($dto));
    }

    public function storeSavedAddress(SavedAddressStoreDto $dto): JsonResource
    {
        $this->savedAddersService->storeAddress($dto);

        return SavedAddressResource::collection(
            SavedAddress::query()->where([
                'user_id' => Auth::id(),
                'type' => $dto->type,
            ])->get(),
        );
    }

    public function updateSavedAddress(
        SavedAddressUpdateDto $dto,
        SavedAddress $address,
        SavedAddressType $type,
    ): JsonResource {
        $this->savedAddersService->updateAddress(
            $address,
            $dto,
            $type,
        );

        return SavedAddressResource::collection(
            SavedAddress::query()->where([
                'user_id' => Auth::id(),
                'type' => $type,
            ])->get(),
        );
    }

    public function deleteSavedAddress(SavedAddress $address, SavedAddressType $type): JsonResource
    {
        $this->savedAddersService->deleteSavedAddress($address);

        return SavedAddressResource::collection(
            SavedAddress::query()->where([
                'user_id' => Auth::id(),
                'type' => $type->value,
            ])->get(),
        );
    }

    public function updateProfile(ProfileUpdateDto $dto): JsonResource
    {
        return UserResource::make(
            $this->authService->updateProfile($dto),
        );
    }
}
