<?php

namespace App\Http\Controllers;

use App\Error;
use App\Order;
use App\Status;
use App\Address;
use App\Product;
use App\ProductSchema;
use App\ProductSchemaItem;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'comment' => 'string|max:1000',
            'items' => 'required|array|min:1',
            'deliveryAddress.name' => 'required|string|max:255',
            'deliveryAddress.phone' => 'required|string|max:20',
            'deliveryAddress.address' => 'required|string|max:255',
            'deliveryAddress.zip' => 'required|string|max:16',
            'deliveryAddress.city' => 'required|string|max:255',
            'deliveryAddress.country' => 'required|string|size:2',
        ]);

        if ($request->filled('invoiceAddress.name')) {
            $request->validate([
                'invoiceAddress.name' => 'required|string|max:255',
                'invoiceAddress.phone' => 'required|string|max:20',
                'invoiceAddress.address' => 'required|string|max:255',
                'invoiceAddress.nip' => 'required|string|max:10',
                'invoiceAddress.zip' => 'required|string|max:16',
                'invoiceAddress.city' => 'required|string|max:255',
                'invoiceAddress.country' => 'required|string|size:2',
            ]);
        }

        foreach ($request->items as $item) {
            Validator::make($item, [
                'product_id' => 'required|exists:products,id',
                'qty' => 'required|integer',
                'schemaItems' => 'array',
                'customSchemas' => 'array',
            ])->validate();

            foreach ($item['schemaItems'] as $id) {
                $schemaItem = ProductSchemaItem::find($id);
                if ($schemaItem == NULL || $schemaItem->item->qty < $item['qty']) {
                    return Error::abort('Invalid data.', 400);
                }
            }

            if (count($item['schemaItems']) === 0) {
                $product = Product::find($item['product_id']);
                $schemaItem = $product->schemas()->first()->schemaItems()->first();

                if ($schemaItem->item->qty < $item['qty']) {
                    return Error::abort('Invalid data.', 400);
                }
            }

            foreach ($item['customSchemas'] as $input) {
                $schema = ProductSchema::find($input['schema_id']);
                if ($schema == NULL || $schema->type == 0) {
                    return Error::abort('Invalid data.', 400);
                }

                Validator::make($input, [
                    'value' => 'required|string|max:256',
                ])->validate();
            }
        }

        do {
            $code = Str::upper(Str::random(6));
        } while (Order::firstWhere('code', $code));

        $order = new Order([
            'code' => $code,
            'email' => $request->email,
            'comment' => $request->comment,
        ]);

        $order->delivery_address = Address::firstOrCreate($request->deliveryAddress)->id;
        $order->invoice_address = $request->filled('invoiceAddress.name') ?
            Address::firstOrCreate($request->invoiceAddress)->id : NULL;

        $order->save();

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $price = $product->price;

            foreach($item['schemaItems'] as $id) {
                $price += ProductSchemaItem::find($id)->extra_price;
            }

            $orderItem = $order->items()->create([
                'product_id' => $product->id,
                'qty' => $item['qty'],
                'tax' => $product->tax->value,
                'price' => $price < 0 ? 0 : $price,
            ]);

            $orderItem->schemaItems()->sync($item['schemaItems']);

            if (count($item['schemaItems']) === 0) {
                $schemaItem = $product->schemas()->first()->schemaItems()->first();
                $orderItem->schemaItems()->attach($schemaItem);
            }

            foreach($item['customSchemas'] as $schema) {
                $orderItem->schemaItems()->create([
                    'product_schema_id' => $schema['schema_id'],
                    'value' => $schema['value'],
                    'extra_price' => 0,
                ]);
            }
        }

        // logi
        $order->logs()->create([
            'content' => 'Utworzenie zamówienia.',
            'user' => 'API',
        ]);

        return response()->json([
            'status' => 200,
        ]);
    }
}
