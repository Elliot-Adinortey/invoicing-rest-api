<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Product\ProductServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\RestockProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(public ProductServiceInterface $productService) {}

    public function index(): JsonResponse
    {
        $products = $this->productService->paginate();

        return ApiResponse::success(
            ProductResource::collection($products)->response()->getData(true),
            'Products retrieved successfully.'
        );
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return ApiResponse::success(
            new ProductResource($product),
            'Product created successfully.',
            'success',
            201
        );
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResponse::success(new ProductResource($product), 'Product retrieved successfully.');
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update($product, $request->validated());

        return ApiResponse::success(new ProductResource($product), 'Product updated successfully.');
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return ApiResponse::success(null, 'Product deleted successfully.');
    }

    public function restock(RestockProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->restock($product, $request->validated());

        return ApiResponse::success(new ProductResource($product), 'Product restocked successfully.');
    }
}
