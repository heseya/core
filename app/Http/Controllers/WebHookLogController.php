<?php

namespace App\Http\Controllers;

use App\Http\Resources\WebHookEventLogEntryResource;
use App\Models\WebHookEventLogEntry;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class WebHookLogController extends Controller
{
    public function index(): JsonResource
    {
        return WebHookEventLogEntryResource::collection(
            WebHookEventLogEntry::query()
                ->with('webHook')
                ->orderBy('triggered_at', 'DESC')
                ->paginate(Config::get('pagination.per_page')),
        );
    }
}
