<?php

namespace App\Services;

use App\Dtos\OrderUpdateDto;
use App\Exceptions\OrderException;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\OrderServiceContract;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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
                $dto->getDeliveryAddress()->toArray()
            );
            $invoiceAddress = $this->modifyAddress(
                $order->invoice_address_id,
                $dto->getInvoiceAddress()->toArray(),
                true
            );

            $order->update([
                'email' => $dto->getEmail(),
                'comment' => $dto->getComment(),
                'delivery_address_id' => $deliveryAddress->getKey(),
                'invoice_address_id' => $invoiceAddress ? $invoiceAddress->getKey() : null,
            ]);

            DB::commit();

            return OrderResource::make($order)->response();
        } catch (Exception $error) {
            DB::rollBack();

            throw new OrderException(
                'Error editing the order for id: ' . $order->id,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function modifyAddress(?string $uuid, array $data, bool $isInvoice = false): ?Address
    {
        if ($isInvoice) {
            foreach ($data as $item) {
                if ($item !== null) {
                    $exsistInvoiceAddress = true;
                }
            }
            if (!isset($exsistInvoiceAddress)) {
                return null;
            }
        }

        return Address::updateOrCreate(['id' => $uuid], $data);
    }
}
