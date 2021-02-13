<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\OrderControllerSwagger;
use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\OrderUpdateStatusRequest;
use App\Http\Resources\OrderPublicResource;
use App\Http\Resources\OrderResource;
use App\Mail\OrderUpdateStatus;
use App\Mail\NewOrder;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller implements OrderControllerSwagger
{
    public function index(OrderIndexRequest $request): JsonResource
    {
        $query = Order::search($request->validated());

        if ($request->filled('sort')) {
            $sort = explode(',', $request->input('sort'));

            foreach ($sort as $option) {
                $option = explode(':', $option);

                Validator::make($option, [
                    '0' => 'required|in:code,created_at,id',
                    '1' => 'in:asc,desc',
                ])->validate();

                $order = count($option) > 1 ? $option[1] : 'asc';
                $query->orderBy($option[0], $order);
            }

        } else {
            $query->orderBy('created_at', 'desc');
        }

        return OrderResource::collection(
            $query->paginate(15),
        );
    }

    public function show(Order $order): JsonResource
    {
        return OrderResource::make($order);
    }

    public function showPublic(Order $order): JsonResource
    {
        return OrderPublicResource::make($order);
    }

    public function store(OrderCreateRequest $request)
    {
        $shipping_method = ShippingMethod::findOrFail($request->input('shipping_method_id'));

        $order = new Order([
            'email' => $request->input('email'),
            'comment' => $request->input('comment'),
            'currency' => 'PLN',
            'shipping_method_id' => $shipping_method->getKey(),
            'shipping_price' => $shipping_method->price,
            'status_id' => Status::select('id')->orderBy('created_at')->first()->getKey(),
        ]);

        $order->delivery_address_id = Address::firstOrCreate($request->delivery_address)->getKey();
        $order->invoice_address_id = $request->filled('invoice_address.name') ?
            Address::firstOrCreate($request->invoice_address)->getKey() : null;

        $order->save();

        foreach ($request->input('items', []) as $item) {
            $product = Product::findOrFail($item['product_id']);
            $price = $product->price;

            $order->items()->create([
                'product_id' => $product->getKey(),
                'quantity' => $item['quantity'],
                'price' => $price < 0 ? 0 : $price,
            ]);
        }

        Mail::to($order->email)->send(new NewOrder($order));

        // logs
        $order->logs()->create([
            'content' => 'Utworzenie zamówienia.',
            'user' => 'API',
        ]);

        return OrderPublicResource::make($order);
    }

    public function verify(Request $request)
    {
        $cartItems = [];
        $itemCounts = [];
        $itemUsers = [];
        $usedSchemas = [];

        foreach ($request->input('items') as $item) {
            Validator::make($item, [
                'product_id' => 'required|uuid|exists:products,id',
                'quantity' => 'required|numeric',
                'schema_items' => 'array|nullable',
                'custom_schemas' => 'array|nullable',
            ])->validate();

            $product = Product::find($item['product_id']);

            if (!$product->isPublic()) {
                continue;
            }

            if (!$product->schemas()->where('required', true)->where('type', 0)->exists()) {
                continue;
            }

            $schemaItems = $item['schema_items'] ?? [];
            $customSchemas = $item['custom_schemas'] ?? [];

            $schemas = $product->schemas()->where('required', 1)->get();

            $quit = false;
            foreach ($schemas as $schema) {
                if ($schema->name === null) {
                    $schemaItem = $schema->schemaItems()->first();

                    if (!in_array($schemaItem->id, $schemaItems)) {
                        array_push($schemaItems, $schemaItem->id);
                    }

                    continue;
                }

                if ($schema->type === 0 && !$schema->schemaItems()->whereIn(
                    'id', $schemaItems)->exists()) {

                    $quit = true;
                    break;
                }

                if ($schema->type > 1) {
                    function thisSchema ($value) {
                        return $value['schema_id'] === $schema->id;
                    }

                    $hasSchema = array_filter($customSchemas, 'thisSchema');

                    if (!$hasSchema) {
                        $quit = true;
                        break;
                    }
                }
            }

            if ($quit) {
                continue;
            }

            foreach ($customSchemas as $input) {
                $schema = ProductSchema::find($input['schema_id']);

                if ($schema === null || $schema->type === 0) {
                    $quit = true;
                    break;
                }

                $productId = $schema->product->id;

                if ($item['product_id'] !== $productId) {
                    $quit = true;
                    break;
                }

                Validator::make($input, [
                    'value' => 'required|string|max:256',
                ])->validate();
            }

            if ($quit) {
                continue;
            }

            $currentItemCounts = [];
            $stock = [];

            foreach ($schemaItems as $id) {
                $schemaItem = ProductSchemaItem::find($id);

                if ($schemaItem === null) {
                    $quit = true;
                    break;
                }

                $schema = $schemaItem->schema;

                if ($schema->type !== 0) {
                    $quit = true;
                    break;
                }

                $productId = $schema->product->id;

                if ($item['product_id'] !== $productId) {
                    $quit = true;
                    break;
                }

                if (in_array($schema->id, $usedSchemas)) {
                    $quit = true;
                    break;
                } else {
                    array_push($usedSchemas, $schema->id);
                }

                $itemId = $schemaItem->item->id;

                if (!isset($currentItemCounts[$itemId])) {
                    $currentItemCounts[$itemId] = 0;
                }

                $currentItemCounts[$itemId] += $item['quantity'];

                if (!isset($itemUsers[$itemId])) {
                    $itemUsers[$itemId] = [];
                }

                if ($schemaItem->item->quantity <= 0) {
                    $quit = true;
                    break;
                }

                $stock[$itemId] = $schemaItem->item->quantity;
            }

            if ($quit) {
                continue;
            }

            $enough = true;

            foreach ($currentItemCounts as $key => $value) {
                if (!isset($itemCounts[$key])) {
                    $itemCounts[$key] = 0;
                }

                $itemCounts[$key] += $value;

                if ($stock[$key] < $itemCounts[$key]) {
                    foreach ($itemUsers[$itemId] as $cartItem) {
                        $cartItems[$cartItem]['enough'] = false;
                    }

                    $enough = false;
                }
            }

            $cartItemId = $item['cartitem_id'];

            if (!$quit) {
                if (!in_array($cartItemId, $itemUsers[$itemId])) {
                    array_push($itemUsers[$itemId], $cartItemId);
                }
            }

            $cartItems[$cartItemId] = [
                'cartitem_id' => $cartItemId,
                'enough' => $enough,
            ];
        }

        $cartItems = array_values($cartItems);

        return response()->json(['data' => $cartItems]);
    }

    public function updateStatus(OrderUpdateStatusRequest $request, Order $order): JsonResource
    {
        $order->update([
            'status_id' => $request->input('status_id'),
        ]);

        Mail::to($order->email)->send(new OrderUpdateStatus($order));

        return OrderResource::make($order);
    }
}
