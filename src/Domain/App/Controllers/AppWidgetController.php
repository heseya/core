<?php

declare(strict_types=1);

namespace Domain\App\Controllers;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\User;
use Domain\App\Dtos\AppWidgetCreateDto;
use Domain\App\Dtos\AppWidgetUpdateDto;
use Domain\App\Models\AppWidget;
use Domain\App\Requests\AppWidgetIndexRequest;
use Domain\App\Resources\AppWidgetResource;
use Domain\App\Services\AppWidgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

final class AppWidgetController extends Controller
{
    public function __construct(private readonly AppWidgetService $widgetServicce) {}

    public function index(AppWidgetIndexRequest $request): JsonResource
    {
        /** @var User|App|null $user */
        $user = $request->user();

        if ($user instanceof User) {
            Gate::authorize('app_widgets.show');
        }

        $criteria = [];

        if ($user instanceof App) {
            $criteria['app_id'] = $user->getKey();
        } else {
            $criteria['permissions'] = true;
        }

        if ($request->filled('section')) {
            $criteria['section'] = $request->string('section');
        }

        $widgets = AppWidget::query()
            ->searchByCriteria($criteria)
            ->paginate(Config::get('pagination.per_page'));

        return AppWidgetResource::collection($widgets);
    }

    public function store(AppWidgetCreateDto $dto): JsonResource
    {
        Gate::inspect('app_widgets.add');

        return AppWidgetResource::make($this->widgetServicce->create($dto));
    }

    public function update(AppWidget $widget, AppWidgetUpdateDto $dto): JsonResource
    {
        /** @var User|App|null $user */
        $user = request()->user();
        if ($user instanceof App) {
            if ($user->getKey() !== $widget->app_id && Gate::denies('app_widgets.edit')) {
                throw new ClientException(Exceptions::CLIENT_WIDGET_NOT_CREATED_BY_THIS_APP);
            }
        } else {
            Gate::authorize('app_widgets.edit');
        }

        return AppWidgetResource::make($this->widgetServicce->update($widget, $dto));
    }

    public function destroy(AppWidget $widget): JsonResponse
    {
        /** @var User|App|null $user */
        $user = request()->user();
        if ($user instanceof App) {
            if ($user->getKey() !== $widget->app_id && Gate::denies('app_widgets.remove')) {
                throw new ClientException(Exceptions::CLIENT_WIDGET_NOT_CREATED_BY_THIS_APP);
            }
        } else {
            Gate::authorize('app_widgets.remove');
        }

        if ($this->widgetServicce->delete($widget)) {
            return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
        }

        return Response::json(null, JsonResponse::HTTP_CONFLICT);
    }
}
