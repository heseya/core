<?php

namespace App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Exceptions\StoreException;
use App\Services\Contracts\AuditServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OwenIt\Auditing\Contracts\Auditable;

class AuditService implements AuditServiceContract
{
    public function getAuditsForModel(string $class, string $id): Collection
    {
        $class = (string) Str::of($class)->singular()->camel()->ucfirst()->start('\\App\\Models\\');

        /** @var Auditable $model */
        $model = $class::select('id')->findOrFail($id);

        if (!($model instanceof Auditable)) {
            throw new ClientException(Exceptions::CLIENT_MODEL_NOT_AUDITABLE);
        }

        return $model->audits()->with('user')->orderBy('created_at', 'DESC')->get();
    }
}
