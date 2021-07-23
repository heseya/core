<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Pagination
{
    public const LIMIT_NAME = 'limit';

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->exists(self::LIMIT_NAME)) {
            $validator = Validator::make($request->only(self::LIMIT_NAME), [
                'limit' => 'required|integer',
            ]);

            if ($validator->invalid()) {
                return redirect()->back()->withErrors(
                    $validator->errors()->toArray()
                );
            }

            config(['services.pagination.per_page' => $request->input(self::LIMIT_NAME)]);
        }

        return $response;
    }
}
