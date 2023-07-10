<?php

namespace App\Http\Controllers;

use App\Dtos\MediaAttachmentDto;
use App\Dtos\MediaAttachmentUpdateDto;
use App\Dtos\ProductCreateDto;
use App\Dtos\ProductSearchDto;
use App\Dtos\ProductUpdateDto;
use App\Http\Requests\MediaAttachmentCreateRequest;
use App\Http\Requests\MediaAttachmentUpdateRequest;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\MediaAttachmentResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ResourceCollection;
use App\Models\MediaAttachment;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\Contracts\MediaAttachmentServiceContract;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function __construct(
        private ProductServiceContract $productService,
        private ProductRepositoryContract $productRepository,
        private MediaAttachmentServiceContract $attachmentService,
    ) {}

    public function index(ProductIndexRequest $request): JsonResource
    {
        $products = $this->productRepository->search(
            ProductSearchDto::instantiateFromRequest($request),
        );

        /** @var ResourceCollection $products */
        $products = ProductResource::collection($products);

        return $products->full($request->boolean('full'));
    }

    public function show(Product $product): JsonResource
    {
        if (Gate::denies('products.show_hidden') && !$product->public) {
            throw new NotFoundHttpException();
        }

        return ProductResource::make($product);
    }

    public function store(ProductCreateRequest $request): JsonResource
    {
        $product = $this->productService->create(
            ProductCreateDto::instantiateFromRequest($request),
        );

        return ProductResource::make($product);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResource
    {
        $product = $this->productService->update(
            $product,
            ProductUpdateDto::instantiateFromRequest($request),
        );

        return ProductResource::make($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return Response::json(null, 204);
    }

    public function addAttachment(MediaAttachmentCreateRequest $request, Product $product): JsonResource
    {
        $attachment = $this->attachmentService->addAttachment(
            $product,
            MediaAttachmentDto::instantiateFromRequest($request),
        );

        return MediaAttachmentResource::make($attachment);
    }

    public function editAttachment(MediaAttachmentUpdateRequest $request, Product $product, MediaAttachment $attachment): JsonResource
    {
        $attachment = $this->attachmentService->editAttachment(
            $attachment,
            MediaAttachmentUpdateDto::instantiateFromRequest($request),
        );

        return MediaAttachmentResource::make($attachment);
    }

    public function deleteAttachment(Product $product, MediaAttachment $attachment): JsonResponse
    {
        $this->attachmentService->removeAttachment($attachment);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
