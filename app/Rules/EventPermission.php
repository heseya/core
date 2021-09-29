<?php

namespace App\Rules;

use App\Enums\EventPermissionType;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class EventPermission implements Rule
{
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value)
    {
        $permissions = $this->getRequiredPermissions($value);
        foreach ($permissions as $permission) {
            if (!$this->user->can($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message()
    {
        return 'No permissions to events in :attribute.';
    }

    private function getRequiredPermissions(array $events): array|false
    {
        $result = [];
        foreach ($events as $event) {
            $permissions = EventPermissionType::coerce(Str::upper(Str::snake($event)));
            array_push($result, ...explode(';', $permissions));
        }
        return array_unique($result);
    }
}
