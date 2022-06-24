<?php

namespace App\Http\Controllers;

use App\Dtos\RegisterDto;
use App\Dtos\SavedAddressDto;
use App\Dtos\TFAConfirmDto;
use App\Dtos\TFAPasswordDto;
use App\Dtos\TFASetupDto;
use App\Enums\SavedAddressType;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordChangeRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Http\Requests\PasswordResetSaveRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\RegisterRequest;
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
use App\Services\Contracts\AuthServiceContract;
use App\Services\Contracts\SavedAddressServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class AuthController extends Controller
{
    public function __construct(
        private AuthServiceContract $authService,
        private AppServiceContract $appService,
        private SavedAddressServiceContract $savedAddresService,
    ) {
    }

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

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function resetPassword(PasswordResetRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->input('email'), $request->input('redirect_url'));

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function showResetPasswordForm(?string $token = null, ?string $email = null): JsonResource
    {
        $user = $this->authService->showResetPasswordForm(
            $email,
            $token
        );

        return UserResource::make($user);
    }

    public function saveResetPassword(PasswordResetSaveRequest $request): JsonResponse
    {
        $this->authService->saveResetPassword(
            $request->input('email'),
            $request->input('token'),
            $request->input('password')
        );

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function changePassword(PasswordChangeRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authService->changePassword(
            $user,
            $request->input('password'),
            $request->input('password_new')
        );

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
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

        $prefix = $this->authService->isAppAuthenticated()
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
            $this->authService->confirmTFA(TFAConfirmDto::instantiateFromRequest($request))
        );
    }

    public function generateRecoveryCodes(TFAPasswordRequest $request): JsonResource
    {
        $dto = TFAPasswordDto::instantiateFromRequest($request);

        return TFARecoveryCodesResource::make($this->authService->generateRecoveryCodes($dto));
    }

    public function removeTFA(TFAPasswordRequest $request): JsonResponse
    {
        $this->authService->removeTFA(TFAPasswordDto::instantiateFromRequest($request));

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function removeUsersTFA(User $user): JsonResponse
    {
        Gate::inspect('removeUserTFA', [User::class, $user]);

        $this->authService->removeUsersTFA($user);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function register(RegisterRequest $request): JsonResource
    {
        return UserResource::make($this->authService->register(RegisterDto::instantiateFromRequest($request)));
    }

    public function storeSavedAddress(SavedAddressStoreRequest $request, string $type): JsonResource
    {
        $this->savedAddresService->storeAddress(
            SavedAddressDto::instantiateFromRequest($request),
            SavedAddressType::from($type)
        );

        return SavedAddressResource::collection(
            SavedAddress::where([
                'user_id' => Auth::id(),
                'type' => $type,
            ])->get()
        );
    }

    public function updateSavedAddress(
        SavedAddressUpdateRequest $request,
        SavedAddress $address,
        string $type
    ): JsonResource {
        $this->savedAddresService->updateAddress(
            $address,
            SavedAddressDto::instantiateFromRequest($request),
            SavedAddressType::from($type)
        );

        return SavedAddressResource::collection(
            SavedAddress::where([
                'user_id' => Auth::id(),
                'type' => $type,
            ])->get()
        );
    }

    public function deleteSavedAddress(SavedAddress $address, string $type): JsonResource
    {
        $this->savedAddresService->deleteSavedAddress($address);

        return SavedAddressResource::collection(
            SavedAddress::where([
                'user_id' => Auth::id(),
                'type' => $type,
            ])->get()
        );
    }

    public function updateProfile(ProfileUpdateRequest $request): JsonResource
    {
        return UserResource::make($this->authService->updateProfile(
            $request->input('name'),
            $request->input('consents')
        ));
    }
}
