<?php

namespace App\Http\Controllers;

use App\Dtos\CouponDto;
use App\Dtos\CouponIndexDto;
use App\Dtos\SaleDto;
use App\Dtos\SaleIndexDto;
use App\Http\Requests\CouponCreateRequest;
use App\Http\Requests\CouponIndexRequest;
use App\Http\Requests\CouponUpdateRequest;
use App\Http\Requests\SaleCreateRequest;
use App\Http\Requests\SaleIndexRequest;
use App\Http\Requests\SaleUpdateRequest;
use App\Http\Resources\CouponResource;
use App\Http\Resources\SaleResource;
use App\Models\Discount;
use App\Services\Contracts\DiscountServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DiscountController extends Controller
{
    public function __construct(
        private DiscountServiceContract $discountService,
    ) {}

    public function indexCoupons(CouponIndexRequest $request): JsonResource
    {
        return CouponResource::collection(
            $this->discountService->index(CouponIndexDto::instantiateFromRequest($request)),
        );
    }

    public function indexSales(SaleIndexRequest $request): JsonResource
    {
        return SaleResource::collection(
            $this->discountService->index(SaleIndexDto::instantiateFromRequest($request)),
        );
    }

    public function showCoupon(Discount $coupon): JsonResource
    {
        Gate::inspect('coupon', [$coupon]);

        return CouponResource::make($coupon);
    }

    public function showCouponByCode(Discount $coupon): JsonResource
    {
        Gate::inspect('coupon', [$coupon]);

        if ($coupon->active) {
            return CouponResource::make($coupon);
        }

        throw new NotFoundHttpException();
    }

    public function showSale(Discount $sale): JsonResource
    {
        Gate::inspect('sale', [$sale]);

        return SaleResource::make($sale);
    }

    public function storeCoupon(CouponCreateRequest $request): JsonResource
    {
        return CouponResource::make(
            $this->discountService->store(CouponDto::instantiateFromRequest($request)),
        );
    }

    public function storeSale(SaleCreateRequest $request): JsonResource
    {
        return SaleResource::make(
            $this->discountService->store(SaleDto::instantiateFromRequest($request)),
        );
    }

    public function updateCoupon(Discount $coupon, CouponUpdateRequest $request): JsonResource
    {
        Gate::inspect('coupon', [$coupon]);

        return CouponResource::make(
            $this->discountService->update($coupon, CouponDto::instantiateFromRequest($request)),
        );
    }

    public function updateSale(Discount $sale, SaleUpdateRequest $request): JsonResource
    {
        Gate::inspect('sale', [$sale]);

        return SaleResource::make(
            $this->discountService->update($sale, SaleDto::instantiateFromRequest($request)),
        );
    }

    public function destroyCoupon(Discount $coupon): JsonResponse
    {
        Gate::inspect('coupon', [$coupon]);
        $this->discountService->destroy($coupon);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function destroySale(Discount $sale): JsonResponse
    {
        Gate::inspect('sale', [$sale]);
        $this->discountService->destroy($sale);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
