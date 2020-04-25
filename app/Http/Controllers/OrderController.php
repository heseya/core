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
use App\Http\Resources\OrderPublicResource;

class OrderController extends Controller
{
    /**
     * @OA\Get(
     *   path="/orders",
     *   summary="orders list",
     *   tags={"Orders"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           ref="#/components/schemas/Order",
     *         )
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function index()
    {
        $orders = Order::paginate(15);

        return OrderResource::collection($orders);
    }

    /**
     * @OA\Get(
     *   path="/orders/id:{id}",
     *   summary="order view",
     *   tags={"Orders"},
     *   @OA\Parameter(
     *     name="code",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="D3PT88",
     *     )
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
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function view(Order $order)
    {
        return new OrderResource($order);
    }

    /**
     * @OA\Get(
     *   path="/orders/{code}",
     *   summary="public order view",
     *   tags={"Orders"},
     *   @OA\Parameter(
     *     name="code",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="D3PT88",
     *     )
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
    public function viewPublic(Order $order)
    {
        return new OrderPublicResource($order);
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

        return (new OrderPublicResource($order))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Post(
     *   path="/orders/verify",
     *   summary="verify cart",
     *   tags={"Orders"},
     *   @OA\RequestBody(
     *     request="OrderCreate",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="items",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(
     *             property="cartitem_id",
     *             type="string",
     *           ),
     *           @OA\Property(
     *             property="product_id",
     *             type="integer",
     *           ),
     *           @OA\Property(
     *             property="quantity",
     *             type="number",
     *           ),
     *           @OA\Property(
     *             property="schema_items",
     *             type="array",
     *             @OA\Items(
     *               type="integer"
     *             )
     *           ),
     *           @OA\Property(
     *             property="custom_schemas",
     *             type="array",
     *             @OA\Items(
     *               type="object",
     *               @OA\Property(
     *                 property="schema_id",
     *                 type="integer",
     *               ),
     *               @OA\Property(
     *                 property="value",
     *                 type="string",
     *               )
     *             )
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(
     *             property="cartitem_id",
     *             type="string",
     *           ),
     *           @OA\Property(
     *             property="enough",
     *             type="boolean",
     *           )
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function verify(Request $request): JsonResponse
    {
        $cartItems = [];
        $itemCounts = [];
        $itemUsers = [];
        $usedSchemas = [];

        foreach ($request->items as $item) {
            Validator::make($item, [
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|numeric',
                'schema_items' => 'array|nullable',
                'custom_schemas' => 'array|nullable',
            ])->validate();

            $product = Product::find($item['product_id']);

            if (!$product->public) {
                continue;
            }

            if (!$product->schemas()->where('required', 1)->where('type', 0)->exists()) {
                continue;
            }

            $schemaItems = $item['schema_items'] ?? [];
            $customSchemas = $item['custom_schemas'] ?? [];

            $schemas = $product->schemas()->where('required', 1)->get();

            $quit = false;
            foreach ($schemas as $schema) {
                if ($schema->name == NULL) {
                    $schemaItem = $schema->schemaItems()->first();

                    if (!in_array($schemaItem->id, $schemaItems)) {
                        array_push($schemaItems, $schemaItem->id);
                    }
                    
                    continue;
                }

                if ($schema->type == 0 && !$schema->schemaItems()->whereIn(
                    'id', $schemaItems)->exists()) {
                    
                    $quit = true;
                    break;
                }

                if ($schema->type > 1) {
                    function thisSchema ($value) {
                        return $value['schema_id'] == $schema->id;
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

                if ($schema == NULL || $schema->type == 0) {
                    $quit = true;
                    break;
                }

                $productId = $schema->product->id;
                
                if ($item['product_id'] != $productId) {
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

                if ($schema->type != 0) {
                    $quit = true;
                    break;
                }
                
                $productId = $schema->product->id;
                
                if ($item['product_id'] != $productId) {
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

    /**
     * @OA\Get(
     *   path="/orders/{code}/pay/{payment_method}",
     *   summary="redirect to payment",
     *   tags={"Orders"},
     *   @OA\Parameter(
     *     name="code",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="payment_method",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="continue",
     *     in="query",
     *     description="URL that the buyer will be redirected to, after making payment",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         @OA\Property(
     *           property="redirectUrl",
     *           type="string"
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function pay(Order $order, $method, Request $request)
    {
        $method = 'paynow';

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

        $payment = $order->payments()->create([
            'method' => $method,
            'amount' => $order->summary,
            'continueUrl' => $request->continue ?? null,
            'currency' => 'PLN',
        ]);

        $payment->update($method_class::generateUrl($payment));

        return response()->json(['data' => [
            'redirectUrl' => $payment->redirectUrl,
        ]]);
    }
}
