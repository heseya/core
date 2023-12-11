<?php

declare(strict_types=1);

namespace Domain\User\Controllers;

use App\DTO\Auth\RegisterDto;
use App\Dtos\SavedAddressDto;
use App\Dtos\TFAConfirmDto;
use App\Dtos\TFAPasswordDto;
use App\Dtos\TFASetupDto;
use App\Dtos\UpdateProfileDto;
use App\Enums\SavedAddressType;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\PasswordResetSaveRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\SavedAddressStoreRequest;
use App\Http\Requests\SavedAddressUpdateRequest;
use App\Http\Requests\TFAConfirmRequest;
use App\Http\Requests\TFAPasswordRequest;
use App\Http\Requests\TFASetupRequest;
use App\Http\Requests\TokenRefreshRequest;
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
use App\Services\Contracts\SavedAddressServiceContract;
use Domain\User\Dtos\ResentEmailVerify;
use Domain\User\Dtos\VerifyEmailDto;
use Domain\User\Services\AuthService;
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
        private readonly AuthService $authService,
        private readonly AppServiceContract $appService,
        private readonly SavedAddressServiceContract $savedAddersService,
    ) {}

    public function login(LoginRequest $request): JsonResource
    {
        $tokens = $this->authService->login(
            $request->input('email'),
            $request->input('password'),
            $request->ip(),
            $request->userAgent(),
            $request->input('code'),
        );

        return AuthResource::make($tokens);
    }

    public function refresh(TokenRefreshRequest $request): JsonResource
    {
        $tokens = $this->authService->refresh(
            $request->input('refresh_token'),
            $request->ip(),
            $request->userAgent(),
        );

        return AuthResource::make($tokens);
    }

    public function logout(): HttpResponse
    {
        $this->authService->logout();

        return Response::noContent();
    }

    public function resetPassword(PasswordResetRequest $request): HttpResponse
    {
        $this->authService->resetPassword($request->input('email'), $request->input('redirect_url'));

        return Response::noContent();
    }

    public function showResetPasswordForm(?string $token = null, ?string $email = null): JsonResource
    {
        $user = $this->authService->showResetPasswordForm(
            $email,
            $token,
        );

        return UserResource::make($user);
    }

    public function saveResetPassword(PasswordResetSaveRequest $request): HttpResponse
    {
        $this->authService->saveResetPassword(
            $request->input('email'),
            $request->input('token'),
            $request->input('password'),
        );

        return Response::noContent();
    }

    public function changePassword(PasswordChangeRequest $request): HttpResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authService->changePassword(
            $user,
            $request->input('password'),
            $request->input('password_new'),
        );

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

    public function setupTFA(TFASetupRequest $request): JsonResource
    {
        return TFAResource::make($this->authService->setupTFA(TFASetupDto::instantiateFromRequest($request)));
    }

    public function confirmTFA(TFAConfirmRequest $request): JsonResource
    {
        return TFARecoveryCodesResource::make(
            $this->authService->confirmTFA(TFAConfirmDto::instantiateFromRequest($request)),
        );
    }

    public function generateRecoveryCodes(TFAPasswordRequest $request): JsonResource
    {
        $dto = TFAPasswordDto::instantiateFromRequest($request);

        return TFARecoveryCodesResource::make($this->authService->generateRecoveryCodes($dto));
    }

    public function removeTFA(TFAPasswordRequest $request): HttpResponse
    {
        $this->authService->removeTFA(TFAPasswordDto::instantiateFromRequest($request));

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

    public function storeSavedAddress(SavedAddressStoreRequest $request, SavedAddressType $type): JsonResource
    {
        $this->savedAddersService->storeAddress(
            SavedAddressDto::instantiateFromRequest($request),
            $type,
        );

        return SavedAddressResource::collection(
            SavedAddress::query()->where([
                'user_id' => Auth::id(),
                'type' => $type->value,
            ])->get(),
        );
    }

    public function updateSavedAddress(
        SavedAddressUpdateRequest $request,
        SavedAddress $address,
        SavedAddressType $type,
    ): JsonResource {
        $this->savedAddersService->updateAddress(
            $address,
            SavedAddressDto::instantiateFromRequest($request),
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

    public function updateProfile(ProfileUpdateRequest $request): JsonResource
    {
        return UserResource::make(
            $this->authService->updateProfile(UpdateProfileDto::instantiateFromRequest($request)),
        );
    }

    public function verifyEmail(VerifyEmailDto $dto): HttpResponse
    {
        $this->authService->verifyEmail(${$dto});

        return Response::noContent();
    }

    public function resentEmailVerify(ResentEmailVerify $dto): HttpResponse
    {
        $this->authService->resentVerifyEmail($dto);

        return Response::noContent();
    }
}
