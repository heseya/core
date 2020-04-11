<?php

namespace App\Http\Controllers;

use App\Page;
use App\Error;
use App\Http\Resources\PageResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PageController extends Controller
{
    /**
     * @OA\Get(
     *   path="/pages",
     *   summary="list page",
     *   tags={"Pages"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Page"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(): ResourceCollection
    {
        return PageResource::collection(
            Page::where('public', true)->simplePaginate(14),
        );
    }

    /**
     * @OA\Get(
     *   path="/pages/{slug}",
     *   summary="single page",
     *   tags={"Pages"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Page"
     *       )
     *     )
     *   )
     * )
     */
    public function view(Page $page)
    {
        if ($page->public !== true) {
            return Error::abort('Unauthorized.', 401);
        }

        return new PageResource($page);
    }
}
