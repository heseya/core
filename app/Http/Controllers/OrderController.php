<?php

namespace App\Http\Controllers;

use App\Error;
use App\Order;
use App\Status;
use App\Address;
use App\Product;
use App\ProductSchema;
use App\ShippingMethod;
use App\ProductSchemaItem;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\OrderResource;
use App\Http\Requests\OrderCreateRequest;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function view(Order $order): JsonResponse
    {
        return response()->json([
            'code' => $order->code,
            'statuses' => [
                'payment' => Status::payment($order->payment_status),
                'shop' => Status::shop($order->shop_status),
                'delivery' => Status::delivery($order->delivery_status),
            ],
        ]);
    }

    public function pay(Order $order, $method)
    {
        if (
            $order->payment_status !== 0 ||
            $order->shop_status === 3
        ) {
            return Error::abort('Order not payable.', 406);
        }

        if (!array_key_exists($method, config('payable.aliases'))) {
            return Error::abort('Unkown payment method.', 400);
        }

        $method_class = config('payable.aliases')[$method];

        $payment = $order->payments()
            ->where('method', $method)
            ->where('status', 'NEW')
            ->first();

        if (empty($payment)) {
            $payment = $order->payments()->create([
                'method' => $method,
                'amount' => $order->summary(),
                'currency' => 'PLN',
            ]);

            $payment->update(
                $method_class::generateUrl($payment)
            );
        }

        return response()->json([
            'url' => $payment->url,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/orders",
     *   summary="add new order",
     *   tags={"Orders"},
     *   @OA\RequestBody(
     *     ref="#/components/requestBodies/OrderCreate",
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Order",
     *       )
     *     )
     *   )
     * )
     */
    public function create(OrderCreateRequest $request): JsonResponse
    {
        $shipping_method = ShippingMethod::find($request->shipping_method_id);

        if ($shipping_method === null) {
            return Error::abort('Invalid shipping method.', 400);
        }

        foreach ($request->items as $item) {
            Validator::make($item, [
                'product_id' => 'required|exists:products,id',
                'qty' => 'required|integer',
                'schema_items' => 'array',
                'custom_schemas' => 'array',
            ])->validate();

            foreach ($item['schema_items'] as $id) {

                $schemaItem = ProductSchemaItem::find($id);

                if ($schemaItem === null) {
                    return Error::abort('Schema item with ID ' . $id . ' not exist.' , 400);
                }

                if ($schemaItem->item->qty < $item['qty']) {
                    return Error::abort('Niewystarczająca ilość ' . $schemaItem->item->name, 400);
                }
            }

            if (isset($item['custom_schemas'])) {
                if (count($item['schema_items']) === 0) {
                    $product = Product::find($item['product_id']);
                    $schemaItem = $product->schemas()->first()->schemaItems()->first();

                    if ($schemaItem->item->qty < $item['qty']) {
                        return Error::abort('Invalid data.', 400);
                    }
                }
            }

            if (isset($item['custom_schemas'])) {
                foreach ($item['custom_schemas'] as $input) {
                    $schema = ProductSchema::find($input['schema_id']);
                    if ($schema == NULL || $schema->type == 0) {
                        return Error::abort('Invalid data.', 400);
                    }

                    Validator::make($input, [
                        'value' => 'required|string|max:256',
                    ])->validate();
                }
            }
        }

        do {
            $code = Str::upper(Str::random(6));
        } while (Order::firstWhere('code', $code));

        $order = new Order([
            'code' => $code,
            'email' => $request->email,
            'comment' => $request->comment,
            'shipping_method_id' => $shipping_method->id,
            'shipping_price' => $shipping_method->price,
        ]);

        $order->delivery_address = Address::firstOrCreate($request->delivery_address)->id;
        $order->invoice_address = $request->filled('invoice_address.name') ?
            Address::firstOrCreate($request->invoice_address)->id : NULL;

        $order->save();

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $price = $product->price;

            foreach($item['schema_items'] as $id) {
                $price += ProductSchemaItem::find($id)->extra_price;
            }

            $orderItem = $order->items()->create([
                'product_id' => $product->id,
                'qty' => $item['qty'],
                'price' => $price < 0 ? 0 : $price,
            ]);

            $orderItem->schemaItems()->sync($item['schema_items']);

            if (count($item['schema_items']) === 0) {
                $schemaItem = $product->schemas()->first()->schemaItems()->first();
                $orderItem->schemaItems()->attach($schemaItem);
            }

            if (isset($item['custom_schemas'])) {
                foreach($item['custom_schemas'] as $schema) {
                    $orderItem->schemaItems()->create([
                        'product_schema_id' => $schema['schema_id'],
                        'value' => $schema['value'],
                        'extra_price' => 0,
                    ]);
                }
            }
        }

        // logi
        $order->logs()->create([
            'content' => 'Utworzenie zamówienia.',
            'user' => 'API',
        ]);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(202);
    }
}
