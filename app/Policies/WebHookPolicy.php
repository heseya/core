<?php

namespace App\Policies;

use App\Exceptions\WebHookCreatorException;
use App\Exceptions\WebHookEventException;
use App\Models\App;
use App\Models\User;
use App\Models\WebHook;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Config;

class WebHookPolicy
{
    use HandlesAuthorization;

    public function create(User|App $user, array $webHook): Response
    {
        return $this->checkPermissions($webHook['events'], $webHook['with_issuer'], $webHook['with_hidden'], $user);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User|App $user
     * @param WebHook $webHook
     *
     * @return Response
     */
    public function update(User|App $user, WebHook $webHook, array $newWebHook): Response
    {
        $this->canChange($user, $webHook, 'edit');

        return $this->checkPermissions(
            $newWebHook['events'] ?? $webHook->events,
            $newWebHook['with_issuer'] ?? $webHook->with_issuer,
            $newWebHook['with_hidden'] ?? $webHook->with_hidden,
            $user
        );
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User|App $user
     * @param WebHook $webHook
     *
     * @return Response
     */
    public function delete(User|App $user, WebHook $webHook): Response
    {
        return $this->canChange($user, $webHook, 'delete');
    }

    private function canChange(User|App $user, WebHook $webHook, string $method): Response
    {
        // Webhook stworzony przez aplikację
        if ($webHook->model_type === App::class) {
            // Zalogowana aplikacja i aplikacja stworzyła danego Webhooka
            return ($user instanceof App) && $user->getKey() === $webHook->creator_id
                ? Response::allow()
                : throw new WebHookCreatorException('Only application can ' . $method . ' this WebHook.');
        }
        // Webhook stworzony przez użytkownika
        return $user instanceof User
            ? Response::allow()
            : throw new WebHookCreatorException('Only user can ' . $method . ' this WebHook.');
    }

    private function getRequiredPermissions(array $events, bool $with_issuer, bool $with_hidden): array|false
    {
        $result = [];
        $event_permissions = Config::get('events.permissions');
        $event_permissions_hidden = Config::get('events.permissions_hidden');
        if ($with_issuer) {
            array_push($result, ...['users.show', 'apps.show']);
        }
        foreach ($events as $event) {
            $permissions = $event_permissions[$event];

            if ($permissions) {
                array_push($result, ...$permissions);
            }

            if ($with_hidden && array_key_exists($event, $event_permissions_hidden)) {
                array_push($result, ...$event_permissions_hidden[$event]);
            }
        }
        return array_unique($result);
    }

    private function checkPermissions(array $events, bool $with_issuer, bool $with_hidden, User|App $user): Response
    {
        $permissions = $this->getRequiredPermissions($events, $with_issuer, $with_hidden);
        if (!$user->can($permissions)) {
            throw new WebHookEventException('No required permissions to events.');
        }
        return Response::allow();
    }
}