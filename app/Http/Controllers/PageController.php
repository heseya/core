<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Exceptions\Error;
use Illuminate\Http\Request;
use App\Http\Resources\PageResource;
use Illuminate\Support\Facades\Auth;

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
    public function index()
    {
        $query = Page::select();

        if (!Auth::check()) {
            $query->where('public', true);
        }


        return PageResource::collection($query->simplePaginate(14));
    }

    /**
     * @OA\Get(
     *   path="/pages/{slug}",
     *   summary="single page view",
     *   tags={"Pages"},
     *   @OA\Parameter(
     *     name="slug",
     *     in="path",
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
     *         ref="#/components/schemas/Page"
     *       )
     *     )
     *   )
     * )
     */

    /**
     * @OA\Get(
     *   path="/pages/id:{id}",
     *   summary="alias",
     *   tags={"Pages"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Page"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function view(Page $page)
    {
        if (!Auth::check() && $page->public !== true) {
            return Error::abort('Unauthorized.', 401);
        }

        return PageResource::make($page);
    }

     /**
     * @OA\Post(
     *   path="/pages",
     *   summary="add new page",
     *   tags={"Pages"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Page",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Page",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'public' => 'boolean',
            'content_md' => 'string|nullable',
        ]);

        $page = Page::create($request->all());

        return PageResource::make($page)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Patch(
     *   path="/pages/id:{id}",
     *   summary="update page",
     *   tags={"Pages"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Page",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Page",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(Page $page, Request $request)
    {
        $request->validate([
            'name' => 'string|max:255',
            'price' => 'string|max:255',
            'public' => 'boolean',
            'content_md' => 'string|nullable',
        ]);

        $page->update($request->all());

        return PageResource::make($page);
    }

    /**
     * @OA\Delete(
     *   path="/pages/id:{id}",
     *   summary="delete page",
     *   tags={"Pages"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
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
    public function delete(Page $page)
    {
        $page->delete();

        return response()->json(null, 204);
    }
}
