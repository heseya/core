<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * App\Models\Address
 *
 * @OA\Schema ()
 * @mixin IdeHelperAddress
 * @property string $id
 * @property string|null $name
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $vat
 * @property string|null $zip
 * @property string|null $city
 * @property string|null $country
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Country|null $countryModel
 * @property-read string $phone_simple
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $orders
 * @property-read int|null $orders_count
 * @method static \Database\Factories\AddressFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Address query()
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereVat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Address whereZip($value)
 */
	class IdeHelperAddress extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\App
 *
 * @mixin IdeHelperApp
 * @property string $id
 * @property string|null $name
 * @property string $url
 * @property string $key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\AppFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|App newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|App newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|App query()
 * @method static \Illuminate\Database\Eloquent\Builder|App whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|App whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|App whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|App whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|App whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|App whereUrl($value)
 */
	class IdeHelperApp extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Country
 *
 * @OA\Schema ()
 * @OA\Property (
 *   property="code",
 *   type="string",
 *   example="PL",
 * )
 * @OA\Property (
 *   property="name",
 *   type="string",
 *   example="Poland",
 * )
 * @mixin IdeHelperCountry
 * @property string $code
 * @property string $name
 * @method static \Illuminate\Database\Eloquent\Builder|Country newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Country newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Country query()
 * @method static \Illuminate\Database\Eloquent\Builder|Country whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Country whereName($value)
 */
	class IdeHelperCountry extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Deposit
 *
 * @OA\Schema ()
 * @mixin IdeHelperDeposit
 * @property string $id
 * @property float $quantity
 * @property string $item_id
 * @property string|null $order_product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Item $items
 * @method static \Database\Factories\DepositFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit query()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereOrderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereUpdatedAt($value)
 */
	class IdeHelperDeposit extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Discount
 *
 * @OA\Schema ()
 * @mixin IdeHelperDiscount
 * @property string $id
 * @property string $code
 * @property string|null $description
 * @property float $discount
 * @property DiscountType $type
 * @property int $max_uses
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read bool $available
 * @property-read int $uses
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $orders
 * @property-read int|null $orders_count
 * @method static \Database\Factories\DiscountFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Discount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Discount newQuery()
 * @method static \Illuminate\Database\Query\Builder|Discount onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Discount query()
 * @method static \Illuminate\Database\Eloquent\Builder|Discount search(array $params = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Discount whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Discount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Discount whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Discount whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Discount whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Discount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Discount whereMaxUses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Discount whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Discount whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Discount withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Discount withoutTrashed()
 */
	class IdeHelperDiscount extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Item
 *
 * @OA\Schema ()
 * @mixin IdeHelperItem
 * @property string $id
 * @property string $name
 * @property string|null $sku
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Deposit[] $deposits
 * @property-read int|null $deposits_count
 * @property-read float $quantity
 * @method static \Database\Factories\ItemFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Item newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Item newQuery()
 * @method static \Illuminate\Database\Query\Builder|Item onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Item query()
 * @method static \Illuminate\Database\Eloquent\Builder|Item search(array $params = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Item sort(?string $sortString = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Item whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Item withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Item withoutTrashed()
 */
	class IdeHelperItem extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Media
 *
 * @OA\Schema ()
 * @mixin IdeHelperMedia
 * @property string $id
 * @property int $type
 * @property string $url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\MediaFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Media newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Media newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Media query()
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Media whereUrl($value)
 */
	class IdeHelperMedia extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Option
 *
 * @OA\Schema ()
 * @mixin IdeHelperOption
 * @property string $id
 * @property string $name
 * @property float $price
 * @property bool $disabled
 * @property string $schema_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $order
 * @property-read bool $available
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Item[] $items
 * @property-read int|null $items_count
 * @method static \Database\Factories\OptionFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Option newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Option newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Option query()
 * @method static \Illuminate\Database\Eloquent\Builder|Option whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Option whereDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Option whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Option whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Option whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Option wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Option whereSchemaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Option whereUpdatedAt($value)
 */
	class IdeHelperOption extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Order
 *
 * @OA\Schema ()
 * @mixin IdeHelperOrder
 * @property string $id
 * @property string $code
 * @property string $email
 * @property string $currency
 * @property string|null $comment
 * @property string|null $shipping_number
 * @property float $shipping_price
 * @property string|null $status_id
 * @property string|null $shipping_method_id
 * @property string|null $delivery_address_id
 * @property string|null $invoice_address_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Address|null $deliveryAddress
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Deposit[] $deposits
 * @property-read int|null $deposits_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Discount[] $discounts
 * @property-read int|null $discounts_count
 * @property-read bool $payable
 * @property-read float $payed_amount
 * @property-read float $summary
 * @property-read \App\Models\Address|null $invoiceAddress
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Payment[] $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrderProduct[] $products
 * @property-read int|null $products_count
 * @property-read \App\Models\ShippingMethod|null $shippingMethod
 * @property-read \App\Models\Status|null $status
 * @method static \Database\Factories\OrderFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order search(array $params = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Order sort(?string $sortString = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereDeliveryAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereInvoiceAddressId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShippingMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShippingNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereShippingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdatedAt($value)
 */
	class IdeHelperOrder extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\OrderProduct
 *
 * @OA\Schema ()
 * @mixin IdeHelperOrderProduct
 * @property string $id
 * @property float $quantity
 * @property float $price
 * @property string $order_id
 * @property string $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Deposit[] $deposits
 * @property-read int|null $deposits_count
 * @property-read \App\Models\Order $order
 * @property-read \App\Models\Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\OrderSchema[] $schemas
 * @property-read int|null $schemas_count
 * @method static \Database\Factories\OrderProductFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderProduct whereUpdatedAt($value)
 */
	class IdeHelperOrderProduct extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\OrderSchema
 *
 * @OA\Schema ()
 * @mixin IdeHelperOrderSchema
 * @property string $id
 * @property string $name
 * @property string $value
 * @property float $price
 * @property string $order_product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\OrderSchemaFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderSchema newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderSchema newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderSchema query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderSchema whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderSchema whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderSchema whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderSchema whereOrderProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderSchema wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderSchema whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderSchema whereValue($value)
 */
	class IdeHelperOrderSchema extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\PackageTemplate
 *
 * @mixin IdeHelperPackageTemplate
 * @property string $id
 * @property string $name
 * @property float $weight
 * @property int $width
 * @property int $height
 * @property int $depth
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\PackageTemplateFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder|PackageTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageTemplate whereDepth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageTemplate whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageTemplate whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageTemplate whereWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PackageTemplate whereWidth($value)
 */
	class IdeHelperPackageTemplate extends \Eloquent implements \App\Models\Swagger\PackageTemplateSwagger {}
}

namespace App\Models{
/**
 * App\Models\Page
 *
 * @mixin IdeHelperPage
 * @property string $id
 * @property string $slug
 * @property bool $public
 * @property string $name
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $content_html
 * @method static \Database\Factories\PageFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Page newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Page newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Page query()
 * @method static \Illuminate\Database\Eloquent\Builder|Page sort(?string $sortString = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereContentHtml($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page wherePublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Page whereUpdatedAt($value)
 */
	class IdeHelperPage extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Payment
 *
 * @OA\Schema ()
 * @mixin IdeHelperPayment
 * @property string $id
 * @property string $order_id
 * @property string|null $external_id
 * @property string $method
 * @property bool $payed
 * @property float $amount
 * @property string|null $redirect_url
 * @property string|null $continue_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Order $order
 * @method static \Database\Factories\PaymentFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereContinueUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereExternalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePayed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereUpdatedAt($value)
 */
	class IdeHelperPayment extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\PaymentMethod
 *
 * @OA\Schema ()
 * @mixin IdeHelperPaymentMethod
 * @property string $id
 * @property string $name
 * @property string $alias
 * @property bool $public
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ShippingMethod[] $shippingMethods
 * @property-read int|null $shipping_methods_count
 * @method static \Database\Factories\PaymentMethodFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod query()
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereAlias($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod wherePublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PaymentMethod whereUpdatedAt($value)
 */
	class IdeHelperPaymentMethod extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Permission
 *
 * @mixin IdeHelperPermission
 * @property-read \Illuminate\Database\Eloquent\Collection|Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Permission permission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder|Permission role($roles, $guard = null)
 */
	class IdeHelperPermission extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Price
 *
 * @OA\Schema ()
 * @mixin IdeHelperPrice
 * @property string $id
 * @property string $model_id
 * @property string $model_type
 * @property float $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Price newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Price newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Price query()
 * @method static \Illuminate\Database\Eloquent\Builder|Price whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Price whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Price whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Price whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Price whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Price whereValue($value)
 */
	class IdeHelperPrice extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\PriceRange
 *
 * @OA\Schema ()
 * @mixin IdeHelperPriceRange
 * @property string $id
 * @property float $start
 * @property string|null $shipping_method_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Price[] $prices
 * @property-read int|null $prices_count
 * @method static \Illuminate\Database\Eloquent\Builder|PriceRange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PriceRange newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PriceRange query()
 * @method static \Illuminate\Database\Eloquent\Builder|PriceRange whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PriceRange whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PriceRange whereShippingMethodId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PriceRange whereStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PriceRange whereUpdatedAt($value)
 */
	class IdeHelperPriceRange extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Product
 *
 * @OA\Schema ()
 * @mixin IdeHelperProduct
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property float $price
 * @property string|null $brand_id
 * @property string|null $category_id
 * @property string|null $description_md
 * @property bool $public
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property float $quantity_step
 * @property int $order
 * @property-read \App\Models\ProductSet|null $brand
 * @property-read \App\Models\ProductSet|null $category
 * @property-read bool $available
 * @property-read string $description_html
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Media[] $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Schema[] $schemas
 * @property-read int|null $schemas_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductSet[] $sets
 * @property-read int|null $sets_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Tag[] $tags
 * @property-read int|null $tags_count
 * @method static \Database\Factories\ProductFactory factory(...$parameters)
 * @method static Builder|Product newModelQuery()
 * @method static Builder|Product newQuery()
 * @method static \Illuminate\Database\Query\Builder|Product onlyTrashed()
 * @method static Builder|Product public()
 * @method static Builder|Product query()
 * @method static Builder|Product search(array $params = [])
 * @method static Builder|Product sort(?string $sortString = null)
 * @method static Builder|Product whereBrandId($value)
 * @method static Builder|Product whereCategoryId($value)
 * @method static Builder|Product whereCreatedAt($value)
 * @method static Builder|Product whereDeletedAt($value)
 * @method static Builder|Product whereDescriptionMd($value)
 * @method static Builder|Product whereId($value)
 * @method static Builder|Product whereName($value)
 * @method static Builder|Product whereOrder($value)
 * @method static Builder|Product wherePrice($value)
 * @method static Builder|Product wherePublic($value)
 * @method static Builder|Product whereQuantityStep($value)
 * @method static Builder|Product whereSlug($value)
 * @method static Builder|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Product withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Product withoutTrashed()
 */
	class IdeHelperProduct extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ProductSet
 *
 * @mixin IdeHelperProductSet
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $parent_id
 * @property bool $public_parent
 * @property bool $public
 * @property int $order
 * @property bool $hide_on_index
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|ProductSet[] $children
 * @property-read int|null $children_count
 * @property-read bool $slug_override
 * @property-read string $slug_suffix
 * @property-read ProductSet|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\ProductSetFactory factory(...$parameters)
 * @method static Builder|ProductSet newModelQuery()
 * @method static Builder|ProductSet newQuery()
 * @method static Builder|ProductSet public()
 * @method static Builder|ProductSet query()
 * @method static Builder|ProductSet reversed()
 * @method static Builder|ProductSet root()
 * @method static Builder|ProductSet search(array $params = [])
 * @method static Builder|ProductSet whereCreatedAt($value)
 * @method static Builder|ProductSet whereHideOnIndex($value)
 * @method static Builder|ProductSet whereId($value)
 * @method static Builder|ProductSet whereName($value)
 * @method static Builder|ProductSet whereOrder($value)
 * @method static Builder|ProductSet whereParentId($value)
 * @method static Builder|ProductSet wherePublic($value)
 * @method static Builder|ProductSet wherePublicParent($value)
 * @method static Builder|ProductSet whereSlug($value)
 * @method static Builder|ProductSet whereUpdatedAt($value)
 */
	class IdeHelperProductSet extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Role
 *
 * @mixin IdeHelperRole
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\RoleFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Role permission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|Role search(array $params = [])
 */
	class IdeHelperRole extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Schema
 *
 * @OA\Schema (
 *   description="Schema allows a product to take on new optional characteristics that can be
 *   chosen by the userand influences the price based on said choices. Schemas can use other
 *   schemas for their price calculation e.g. multiply_schema multiplies price of different
 *   schema based on it's own value. SCHEMAS USED BY OTHERS SHOULD NOT AFFECT THE PRICE
 *   (schema multiplied by multiply_schema adds 0 to the price while multiply_schema adds
 *   the multiplied value)",
 * )
 * @mixin IdeHelperSchema
 * @property string $id
 * @property int $type
 * @property bool $required
 * @property bool $hidden
 * @property string $name
 * @property string|null $description
 * @property float $price
 * @property string|null $min
 * @property string|null $max
 * @property float|null $step
 * @property string|null $default
 * @property string|null $pattern
 * @property string|null $validation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $available
 * @property-read string $type_name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Option[] $options
 * @property-read int|null $options_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Schema[] $usedBySchemas
 * @property-read int|null $used_by_schemas_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Schema[] $usedSchemas
 * @property-read int|null $used_schemas_count
 * @method static \Database\Factories\SchemaFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Schema newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Schema query()
 * @method static \Illuminate\Database\Eloquent\Builder|Schema search(array $params = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Schema sort(?string $sortString = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereHidden($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema wherePattern($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereStep($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Schema whereValidation($value)
 */
	class IdeHelperSchema extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Setting
 *
 * @OA\Schema ()
 * @mixin IdeHelperSetting
 * @property string $id
 * @property string $name
 * @property string $value
 * @property bool $public
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read bool $permanent
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting query()
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting wherePublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Setting whereValue($value)
 */
	class IdeHelperSetting extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\ShippingMethod
 *
 * @OA\Schema ()
 * @mixin IdeHelperShippingMethod
 * @property string $id
 * @property string $name
 * @property bool $public
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $order
 * @property bool $black_list
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Country[] $countries
 * @property-read int|null $countries_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PaymentMethod[] $paymentMethods
 * @property-read int|null $payment_methods_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PaymentMethod[] $paymentMethodsPublic
 * @property-read int|null $payment_methods_public_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PriceRange[] $priceRanges
 * @property-read int|null $price_ranges_count
 * @method static \Database\Factories\ShippingMethodFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|ShippingMethod newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ShippingMethod newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ShippingMethod query()
 * @method static \Illuminate\Database\Eloquent\Builder|ShippingMethod whereBlackList($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShippingMethod whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShippingMethod whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShippingMethod whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShippingMethod whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShippingMethod wherePublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ShippingMethod whereUpdatedAt($value)
 */
	class IdeHelperShippingMethod extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Status
 *
 * @OA\Schema ()
 * @mixin IdeHelperStatus
 * @property string $id
 * @property string $name
 * @property string $color
 * @property bool $cancel
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $order
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Order[] $orders
 * @property-read int|null $orders_count
 * @method static \Database\Factories\StatusFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Status newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Status newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Status query()
 * @method static \Illuminate\Database\Eloquent\Builder|Status whereCancel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Status whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Status whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Status whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Status whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Status whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Status whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Status whereUpdatedAt($value)
 */
	class IdeHelperStatus extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\Tag
 *
 * @OA\Schema ()
 * @mixin IdeHelperTag
 * @property string $id
 * @property string $name
 * @property string $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Product[] $products
 * @property-read int|null $products_count
 * @method static \Database\Factories\TagFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag search(array $params = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag whereUpdatedAt($value)
 */
	class IdeHelperTag extends \Eloquent {}
}

namespace App\Models{
/**
 * App\Models\User
 *
 * @OA\Schema ()
 * @mixin IdeHelperUser
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Client[] $clients
 * @property-read int|null $clients_count
 * @property-read string $avatar
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Passport\Token[] $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Query\Builder|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder|User search(array $params = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User sort(?string $sortString = null)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|User withTrashed()
 * @method static \Illuminate\Database\Query\Builder|User withoutTrashed()
 */
	class IdeHelperUser extends \Eloquent implements \Illuminate\Contracts\Auth\Authenticatable, \Illuminate\Contracts\Auth\Access\Authorizable, \Illuminate\Contracts\Auth\CanResetPassword {}
}

