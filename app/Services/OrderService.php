<?php

namespace App\Services;

use App\Dtos\AddressDto;
use App\Dtos\OrderUpdateDto;
use App\Events\OrderUpdated;
use App\Exceptions\OrderException;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\OrderServiceContract;
use Exception;
use Illuminate\Http\JsonResponse;
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
            $deliveryAddress = $this->modifyAddress(
                $order->delivery_address_id,
                $dto->getDeliveryAddress(),
            );
            $invoiceAddress = $this->modifyAddress(
                $order->invoice_address_id,
                $dto->getInvoiceAddress(),
            );

            $order->forceAudit('delivery_address_id', 'invoice_address_id');

            $order->update([
                'email' => $dto->getEmail() ?? $order->email,
                'comment' => $dto->getComment() ?? $order->comment,
                'delivery_address_id' => $deliveryAddress === null ?
                    (is_object($dto->getDeliveryAddress()) ? null : $order->delivery_address_id) :
                    $deliveryAddress->getKey(),
                'invoice_address_id' => $invoiceAddress === null ?
                    (is_object($dto->getInvoiceAddress()) ? null : $order->invoice_address_id) :
                    $invoiceAddress->getKey(),
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

    private function modifyAddress(?string $uuid, ?AddressDto $addressDto): ?Address
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

        return !isset($exsistAddress) ? null : Address::updateOrCreate(['id' => $uuid], $address);
    }
}
