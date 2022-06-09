<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebHookLogIndexRequest;
use App\Http\Resources\WebHookEventLogEntryResource;
use App\Models\WebHookEventLogEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class WebHookLogController extends Controller
{
    public function index(WebHookLogIndexRequest $request): JsonResource
    {
        /** @var Builder $query */
        $query = WebHookEventLogEntry::searchByCriteria($request->validated());

        return WebHookEventLogEntryResource::collection(
            $query
                ->with('webHook')
                ->orderBy('triggered_at', 'DESC')
                ->paginate(Config::get('pagination.per_page')),
        );
    }
}
