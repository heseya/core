<?php

namespace App\Policies;

use App\Models\App;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderPolicy
{
    use HandlesAuthorization;

    public function indexUserOrder(App|User $user): Response
    {
        if ($user->getAuthIdentifier()) {
            return Response::allow();
        }
        throw new NotFoundHttpException();
    }

    public function showUserOrder(App|User $user, Order $order): Response
    {
        if ($order->buyer && $user->getAuthIdentifier() === $order->buyer->getKey()) {
            return Response::allow();
        }
        throw new NotFoundHttpException();
    }
}
