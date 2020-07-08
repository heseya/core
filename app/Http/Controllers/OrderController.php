<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Address;
use App\Models\Product;
use App\Exceptions\Error;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProductSchema;
use App\Models\ShippingMethod;
use App\Models\ProductSchemaItem;
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
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Full text search.",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="sort",
     *     in="query",
     *     description="Sorting string.",
     *     @OA\Schema(
     *       type="string",
     *       example="code:asc,created_at:desc,id"
     *     ),
     *   ),
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
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'string|max:255',
            'sort' => 'string',
        ]);

        $query = Order::select();

        if ($request->search) {
            $query
                ->where('code', 'LIKE', '%' . $request->search . '%')
                ->orWhere('email', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->sort) {
            $sort = explode(',', $request->sort);

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

        $query = $query->paginate(15);

        return OrderResource::collection($query);
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
        return OrderResource::make($order);
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
        return OrderPublicResource::make($order);
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

        $itemCounts = [];
        $usedSchemas = [];

        $indexedSchemaItems = [];

        $cartItems = 0;

        foreach ($request->items as $item) {
            Validator::make($item, [
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|numeric',
                'schema_items' => 'array|nullable',
                'custom_schemas' => 'array|nullable',
            ])->validate();

            $product = Product::find($item['product_id']);

            if (!$product->public) {
                return Error::abort(
                    'Product with ID ' . $product->id . ' is does not exist.',
                    400,
                );
            }

            if (!$product->schemas()->where('required', true)->where('type', 0)->exists()) {
                return Error::abort(
                    'Product with ID ' . $product->id . ' is invalid.',
                    400,
                );
            }

            $schemaItems = $item['schema_items'] ?? [];
            $customSchemas = $item['custom_schemas'] ?? [];

            $schemas = $product->schemas()->where('required', 1)->get();

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

                    return Error::abort(
                        'No required schema items present.',
                        400,
                    );
                }

                if ($schema->type > 1) {
                    function thisSchema ($value) {
                        return $value['schema_id'] === $schema->id;
                    }

                    $hasSchema = array_filter($customSchemas, 'thisSchema');

                    if (!$hasSchema) {
                        return Error::abort(
                            'No required custom schemas present.',
                            400,
                        );
                    }
                }
            }

            foreach ($customSchemas as $input) {
                $schema = ProductSchema::find($input['schema_id']);

                if ($schema === null || $schema->type === 0) {
                    return Error::abort(
                        'Custom schema with ID ' . $id . ' does not exist.',
                        400,
                    );
                }

                $productId = $schema->product->id;

                if ($item['product_id'] !== $productId) {
                    return Error::abort(
                        'Custom schema with ID ' . $id . ' does not exist.',
                        400,
                    );
                }

                Validator::make($input, [
                    'value' => 'required|string|max:256',
                ])->validate();
            }

            foreach ($schemaItems as $id) {
                $schemaItem = ProductSchemaItem::find($id);

                if ($schemaItem === null) {
                    return Error::abort(
                        'Schema item with ID ' . $id . ' does not exist.',
                        400,
                    );
                }

                $schema = $schemaItem->schema;

                if ($schema->type !== 0) {
                    return Error::abort(
                        'Schema item with ID ' . $id . ' does not exist.',
                        400,
                    );
                }

                $productId = $schema->product->id;

                if ($item['product_id'] !== $productId) {
                    return Error::abort(
                        'Schema item with ID ' . $id . ' does not exist.',
                        400,
                    );
                }

                if (in_array($schema->id, $usedSchemas)) {
                    return Error::abort(
                        'Schema with ID ' . $schema->id . ' used twice.',
                        400,
                    );
                } else {
                    array_push($usedSchemas, $schema->id);
                }

                $itemId = $schemaItem->item->id;

                if (!isset($itemCounts[$itemId])) {
                    $itemCounts[$itemId] = 0;
                }

                $itemCounts[$itemId] += $item['quantity'];

                if ($schemaItem->item->quantity < $itemCounts[$itemId]) {
                    return Error::abort(
                        'Insufficient quantity of ' . $schemaItem->item->name,
                        400,
                    );
                }
            }

            $indexedSchemaItems[$cartItems++] = $schemaItems;
        }

        do {
            $code = Str::upper(Str::random(6));
        } while (Order::where('code', $code)->exists());

        $order = new Order([
            'code' => $code,
            'email' => $request->email,
            'comment' => $request->comment,
            'currency' => 'PLN',
            'shipping_method_id' => $shipping_method->id,
            'shipping_price' => $shipping_method->price,
        ]);

        $order->delivery_address_id = Address::firstOrCreate($request->delivery_address)->id;
        $order->invoice_address_id = $request->filled('invoice_address.name') ?
            Address::firstOrCreate($request->invoice_address)->id : null;

        $order->save();

        $cartItems = 0;

        foreach ($request->items as $item) {
            $product = Product::find($item['product_id']);
            $price = $product->price;
            $schemaItems = $indexedSchemaItems[$cartItems++];

            foreach ($schemaItems as $id) {
                $price += ProductSchemaItem::find($id)->extra_price;
            }

            $orderItem = $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $price < 0 ? 0 : $price,
            ]);

            $orderItem->schemaItems()->sync($schemaItems);

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

        return OrderPublicResource::make($order)
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


    /**
     * @OA\Post(
     *   path="/orders/id:{id}/status",
     *   summary="change order status",
     *   tags={"Orders"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="id",
     *       example="2",
     *     ),
     *   ),
     *   @OA\RequestBody(
     *     request="OrderCreate",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="status_id",
     *         type="integer",
     *       ),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=204,
     *     description="Success",
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function changeStatus(Order $order, Request $request)
    {
        $request->validate([
            'status_id' => 'required|integer|exists:statuses,id',
        ]);

        $order->update([
            'status_id' => $request->status_id,
        ]);

        return response()->json(null, 204);
    }
}
