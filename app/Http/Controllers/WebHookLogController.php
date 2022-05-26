<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebHookIndexRequest;
use App\Http\Resources\WebHookEventLogEntryResource;
use App\Models\WebHookEventLogEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class WebHookLogController extends Controller
{
    public function index(WebHookIndexRequest $request): JsonResource
    {
        /** @var Builder $query */
        $query = WebHookEventLogEntry::searchByCriteria($request->validated());

        if ($request->has('successful')) {
            $request->get('successful') ?
                $query->whereBetween('status_code', [200, 299]) :
                $query->whereNotBetween('status_code', [200, 299]);
        }

        return WebHookEventLogEntryResource::collection(
            $query
                ->with('webHook')
                ->orderBy('triggered_at', 'DESC')
                ->paginate(Config::get('pagination.per_page')),
        );
    }
}
