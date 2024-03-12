<?php

declare(strict_types=1);

namespace Domain\Wishlist\Resources;

use App\Http\Resources\Resource;
use Illuminate\Http\Request;

final class WishlistCheckResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'products_in_wishlist' => $this->resource->toArray(),
        ];
    }
}
