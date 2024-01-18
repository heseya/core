<?php

namespace App\Http\Controllers;

use App\Dtos\MediaAttachmentDto;
use App\Dtos\MediaAttachmentUpdateDto;
use App\Http\Requests\MediaAttachmentCreateRequest;
use App\Http\Requests\MediaAttachmentUpdateRequest;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductShowRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\MediaAttachmentResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ResourceCollection;
use App\Models\MediaAttachment;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\Contracts\MediaAttachmentServiceContract;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Domain\Product\Dtos\ProductCreateDto;
use Domain\Product\Dtos\ProductSearchDto;
use Domain\Product\Dtos\ProductUpdateDto;
use Heseya\Dto\DtoException;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly MediaAttachmentServiceContract $attachmentService,
        private readonly ProductRepository $productRepository,
    ) {}

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function index(ProductIndexRequest $request): JsonResource
    {
        $products = $this->productRepository->search(
            ProductSearchDto::from($request),
        );

        /** @var ResourceCollection $products */
        $products = ProductResource::collection($products);

        return $products->full($request->boolean('full'));
    }

    public function show(ProductShowRequest $request, Product $product): JsonResource
    {
        if (Gate::denies('products.show_hidden') && !$product->public) {
            throw new NotFoundHttpException();
        }

        $product->loadMissing([
            'schemas',
            'schemas.metadata',
            'schemas.metadataPrivate',
            'schemas.options',
            'schemas.options.items',
            'schemas.options.metadata',
            'schemas.options.metadataPrivate',
            'schemas.options.prices',
            'schemas.options.schema',
            'schemas.prices',
            'schemas.usedSchemas',
            'sales.amounts',
            'sales.metadata',
            'sales.metadataPrivate',
            'attachments',
            'attachments.media',
            'attachments.media.metadata',
            'attachments.media.metadataPrivate',
            'items',
            'media',
            'media.metadata',
            'media.metadataPrivate',
            'metadata',
            'metadataPrivate',
            'pages',
            'pages.metadata',
            'pages.metadataPrivate',
            'pricesBase',
            'pricesMax',
            'pricesMaxInitial',
            'pricesMin',
            'pricesMinInitial',
            'productAttributes',
            'publishedTags',
            'relatedSets',
            'relatedSets.childrenPublic',
            'relatedSets.media',
            'relatedSets.media.metadata',
            'relatedSets.media.metadataPrivate',
            'relatedSets.metadata',
            'relatedSets.metadataPrivate',
            'relatedSets.parent',
            'seo',
            'seo.media',
            'seo.media.metadata',
            'seo.media.metadataPrivate',
            'sets',
            'sets.childrenPublic',
            'sets.media',
            'sets.media.metadataPrivate',
            'sets.media.metadata',
            'sets.metadata',
            'sets.metadataPrivate',
            'sets.parent',
            'banner.media',
        ]);
        $product->load(['sales' => fn (BelongsToMany|Builder $hasMany) => $hasMany->withOrdersCount()]); // @phpstan-ignore-line

        return ProductResource::make($product);
    }

    public function store(ProductCreateRequest $request): JsonResource
    {
        $product = $this->productService->create(
            ProductCreateDto::from($request),
        );

        return ProductResource::make($product);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResource
    {
        $product = $this->productService->update(
            $product,
            ProductUpdateDto::from($request),
        );

        return ProductResource::make($product);
    }

    public function destroy(Product $product): HttpResponse
    {
        $this->productService->delete($product);

        return Response::noContent();
    }

    public function addAttachment(MediaAttachmentCreateRequest $request, Product $product): JsonResource
    {
        $attachment = $this->attachmentService->addAttachment(
            $product,
            MediaAttachmentDto::instantiateFromRequest($request),
        );

        return MediaAttachmentResource::make($attachment);
    }

    // TODO: add auth check
    public function editAttachment(
        MediaAttachmentUpdateRequest $request,
        Product $product,
        MediaAttachment $attachment,
    ): JsonResource {
        $attachment = $this->attachmentService->editAttachment(
            $attachment,
            MediaAttachmentUpdateDto::instantiateFromRequest($request),
        );

        return MediaAttachmentResource::make($attachment);
    }

    // TODO: add auth check
    public function deleteAttachment(Product $product, MediaAttachment $attachment): HttpResponse
    {
        $this->attachmentService->removeAttachment($attachment);

        return Response::noContent();
    }
}
