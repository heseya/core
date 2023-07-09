<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditResource;
use App\Services\Contracts\AuditServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditController extends Controller
{
    public function __construct(
        private AuditServiceContract $auditService,
    ) {}

    public function index(string $class, string $id): JsonResource
    {
        $audits = $this->auditService->getAuditsForModel($class, $id);

        return AuditResource::collection($audits);
    }
}
