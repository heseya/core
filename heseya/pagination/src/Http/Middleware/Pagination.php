<?php

namespace Heseya\Pagination\Http\Middleware;

use App\Exceptions\StoreException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Pagination
{
    public const LIMIT_NAME = 'limit';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if ($request->exists(self::LIMIT_NAME)) {
            $validator = Validator::make($request->only(self::LIMIT_NAME), [
                self::LIMIT_NAME => ['integer', 'min:1', 'max:' . config('pagination.max')],
            ]);

            if ($validator->fails()) {
                throw new StoreException($validator->errors()->first());
            }

            config(['pagination.per_page' => $request->input(self::LIMIT_NAME)]);
        }

        return $response;
    }
}
