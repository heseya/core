<?php

declare(strict_types=1);

namespace Domain\Product\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperProductSalesChannel
 */
final class ProductSalesChannel extends Pivot {}
