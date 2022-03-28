<?php

namespace App\Services;

use App\Dtos\AddressDto;
use App\Dtos\OrderIndexDto;
use App\Dtos\OrderUpdateDto;
use App\Enums\ShippingType;
use App\Events\OrderRequestedShipping;
use App\Events\OrderUpdated;
use App\Exceptions\OrderException;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Models\PackageTemplate;
use App\Models\ShippingMethod;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\OrderServiceContract;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class OrderService implements OrderServiceContract
{
    protected DiscountServiceContract $discountService;

    public function __construct(DiscountServiceContract $discountService)
    {
        $this->discountService = $discountService;
    }

    public function calcSummary(Order $order): float
    {
        $value = 0;
        foreach ($order->products as $item) {
            $value += $item->price * $item->quantity;
        }

        $cartValue = $value;
        foreach ($order->discounts as $discount) {
            $value -= $this->discountService->calc($cartValue, $discount);
        }

        $value = ($value < 0 ? 0 : $value) + $order->shipping_price;

        return round($value, 2);
    }

    public function update(OrderUpdateDto $dto, Order $order): JsonResponse
    {
        DB::beginTransaction();

        try {
            $shippingType = ShippingMethod::find($dto->getShippingMethodId())->shipping_type ??
                $order->shippingMethod->shipping_type;

            if ($shippingType !== ShippingType::POINT) {
                $shippingPlace = $dto->getShippingPlace() instanceof AddressDto ?
                    $this->modifyAddress(
                        $order,
                        'shipping_address_id',
                        $dto->getShippingPlace(),
                    ) : $dto->getShippingPlace();
            } else {
                $shippingPlace = Address::find($dto->getShippingPlace())->getKey();
            }

            $billingAddress = $this->modifyAddress(
                $order,
                'billing_address_id',
                $dto->getBillingAddress(),
            );

            $order->update([
                'email' => $dto->getEmail() ?? $order->email,
                'comment' => $dto->getComment() ?? $order->comment,
                'shipping_address_id' => $this->resolveShippingAddress($shippingPlace, $shippingType, $order),
                'billing_address_id' => $billingAddress === null ?
                    (is_object($dto->getBillingAddress()) ? null : $order->billing_address_id) :
                    $billingAddress->getKey(),
                'invoice_requested' => $dto->getInvoiceRequested(),
                'shipping_place' => $this->resolveShippingPlace($shippingPlace, $shippingType, $order),
                'shipping_type' => $shippingType ?? $order->shipping_type,
            ]);

            DB::commit();

            OrderUpdated::dispatch($order);

            return OrderResource::make($order)->response();
        } catch (Exception $error) {
            DB::rollBack();

            throw new OrderException(
                'Error while editing order',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
    }

    public function indexUserOrder(OrderIndexDto $dto): LengthAwarePaginator
    {
        return Order::search(['user_id' => Auth::id()] + $dto->getSearchCriteria())
            ->sort($dto->getSort())
            ->with(['products', 'discounts', 'payments'])
            ->paginate(Config::get('pagination.per_page'));
    }

    public function shippingList(Order $order, string $packageTemplateId): Order
    {
        $packageTemplate = PackageTemplate::findOrFail($packageTemplateId);
        OrderRequestedShipping::dispatch($order, $packageTemplate);

        return $order;
    }

    private function modifyAddress(Order $order, string $attribute, ?AddressDto $addressDto): ?Address
    {
        if ($addressDto === null) {
            return null;
        }

        $address = $addressDto->toArray();
        foreach ($address as $item) {
            if ($item !== null) {
                $exsistAddress = true;
            }
        }

        if (!isset($exsistAddress)) {
            return null;
        }

        $old = Address::find($order->$attribute);
        Cache::add('address.' . $order->$attribute, $old ? ((string) $old) : null);
        $order->forceAudit($attribute);

        return Address::updateOrCreate(['id' => $order->$attribute], $address);
    }

    private function resolveShippingAddress(
        Address|string|null $shippingPlace,
        string $shippingType,
        Order $order
    ): ?string {
        switch ($shippingType) {
            case ShippingType::POINT:
                if ($order->shippingMethod->shipping_type === ShippingType::POINT && !$shippingPlace) {
                    return $order->shipping_address_id;
                }

                return $shippingPlace;
            case ShippingType::ADDRESS:
                if ($order->shippingMethod->shipping_type === ShippingType::ADDRESS && !$shippingPlace) {
                    return $order->shipping_address_id;
                }

                return $shippingPlace->getKey();
            default:
                return null;
        }
    }

    private function resolveShippingPlace(
        Address|string|null $shippingPlace,
        string $shippingType,
        Order $order
    ): ?string {
        switch ($shippingType) {
            case ShippingType::POINT_EXTERNAL:
                if ($order->shippingMethod->shipping_type === ShippingType::POINT && !$shippingPlace) {
                    return $order->shipping_place;
                }

                return $shippingPlace;
            default:
                return null;
        }
    }
}
