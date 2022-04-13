<?php

namespace App\Policies;

use App\Models\App;
use App\Models\Discount;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DiscountPolicy
{
    use HandlesAuthorization;

    public function coupon(User|App $user, Discount $discount): Response
    {
        if ($discount->code !== null) {
            return Response::allow();
        }
        throw new NotFoundHttpException();
    }

    public function sale(User|App $user, Discount $discount): Response
    {
        if ($discount->code === null) {
            return Response::allow();
        }
        throw new NotFoundHttpException();
    }
}
