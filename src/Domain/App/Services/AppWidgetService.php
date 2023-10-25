<?php

declare(strict_types=1);

namespace Domain\App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\App;
use App\Models\User;
use Domain\App\Dtos\AppWidgetCreateDto;
use Domain\App\Dtos\AppWidgetUpdateDto;
use Domain\App\Models\AppWidget;
use Illuminate\Support\Facades\Auth;

final class AppWidgetService
{
    public function create(AppWidgetCreateDto $dto, ?App $app = null): AppWidget
    {
        /** @var User|App|null $creator */
        $creator = $app ?? Auth::user();

        if (is_array($dto->permissions) && !empty($dto->permissions) && (empty($creator) || !$creator->hasAllPermissions($dto->permissions))) {
            $userPermissions = $creator?->getAllPermissions()->pluck('name') ?? [];

            throw new ClientException(Exceptions::CLIENT_ADD_WIDGET_WITH_PERMISSIONS_USER_DONT_HAVE, null, false, ['permissions' => collect($dto->permissions)->diff($userPermissions)]);
        }

        /** @var AppWidget $widget */
        $widget = AppWidget::create(['app_id' => $creator instanceof App ? $creator->id : null] + $dto->except('permissions')->toArray());

        if (is_array($dto->permissions)) {
            $widget->syncPermissions($dto->permissions);
        }

        return $widget;
    }

    public function update(AppWidget $widget, AppWidgetUpdateDto $dto): AppWidget
    {
        $widget->update($dto->except('permissions')->toArray());
        if (is_array($dto->permissions)) {
            $widget->syncPermissions($dto->permissions);
        }

        return $widget->refresh();
    }

    public function delete(AppWidget $widget): bool
    {
        return (bool) $widget->delete();
    }
}
