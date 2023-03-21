<?php

namespace App\Http\Controllers;

use App\Http\Resources\App\AppWidgetResource;
use App\Models\AppWidget;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class AppWidgetController extends Controller
{
    public function index(string $section): JsonResource
    {
        $widgets = AppWidget::searchByCriteria(['section' => $section])
            ->paginate(Config::get('pagination.per_page'));

        return AppWidgetResource::collection($widgets);
    }
}
