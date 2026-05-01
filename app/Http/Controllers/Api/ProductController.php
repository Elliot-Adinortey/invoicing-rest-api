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
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(public ProductServiceInterface $productService) {}

    /**
     * Get all products.
     *
     * @group Products
     *
     * @authenticated
     *
     * @queryParam search string Search by product name. Example: keyboard
     * @queryParam per_page integer Items per page (max 100). Default: 15
     * @queryParam page integer Page number. Default: 1
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'per_page']);

        $products = $this->productService->paginate($filters);

        return ApiResponse::success(
            ProductResource::collection($products)->response()->getData(true),
            'Products retrieved successfully.'
        );
    }

    /**
     * Store a newly created product in storage.
     *
     * @group Products
     *
     * @authenticated
     *
     * @bodyParam product_name string required Example: Product Name
     * @bodyParam description string optional Example: Product Description
     * @bodyParam price decimal required Example: 100.00
     * @bodyParam stock_quantity integer required Example: 10
     *
     * @response 201 {
     *     "data": {
     *         "id": "uuid",
     *         "product_name": "Product Name",
     *         "description": "Product Description",
     *         "price": "100.00",
     *         "stock_quantity": 10,
     *         "created_at": "datetime",
     *         "updated_at": "datetime"
     *     },
     *     "message": "Product created successfully.",
     *     "status": "success",
     *     "status_code": 201
     * }
     */
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

    /**
     * Get a specific product.
     *
     * @group Products
     *
     * @authenticated
     *
     * @urlParam id int required Example: 1
     *
     * @response 200 {
     *     "data": {
     *         "id": "uuid",
     *         "product_name": "Product Name",
     *         "description": "Product Description",
     *         "price": "100.00",
     *         "stock_quantity": 10,
     *         "created_at": "datetime",
     *         "updated_at": "datetime"
     *     },
     *     "message": " Product retrieved successfully.",
     *     "status": "success",
     *     "status_code": 200
     * }
     * @response 404 {
     *     "message": "Product not found.",
     *     "status": "error",
     *     "status_code": 404,
     *     "errors": [],
     *     "code": "NOT_FOUND"
     * }
     */
    public function show(Product $product): JsonResponse
    {
        return ApiResponse::success(new ProductResource($product), 'Product retrieved successfully.');
    }

    /**
     * Update a specific product.
     *
     * @group Products
     *
     * @authenticated
     *
     * @urlParam id int required Example: 1
     *
     * @bodyParam product_name string optional Example: Product Name
     * @bodyParam description string optional Example: Product Description
     * @bodyParam price decimal optional Example: 100.00
     * @bodyParam stock_quantity integer optional Example: 10
     *
     * @response 200 {
     *     "data": {
     *         "id": "uuid",
     *         "product_name": "Updated Product Name",
     *         "description": "Updated Product Description",
     *         "price": "150.00",
     *         "stock_quantity": 20,
     *         "created_at": "datetime",
     *         "updated_at": "datetime"
     *     },
     *     "message": "Product updated successfully.",
     *     "status": "success",
     *     "status_code": 200
     * }
     * @response 404 {
     *     "message": "Product not found.",
     *     "status": "error",
     *     "status_code": 404,
     *     "errors": [],
     *     "code": "NOT_FOUND"
     * }
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update($product, $request->validated());

        return ApiResponse::success(new ProductResource($product), 'Product updated successfully.');
    }

    /**
     * Remove a specific product.
     *
     * @group Products
     *
     * @authenticated
     *
     * @urlParam id int required Example: 1
     *
     * @response 200 {
     *     "data": null,
     *     "message": "Product deleted successfully.",
     *     "status": "success",
     *     "status_code": 200
     * }
     * @response 404 {
     *     "message": "Product not found.",
     *     "status": "error",
     *     "status_code": 404,
     *     "errors": [],
     *     "code": "NOT_FOUND"
     * }
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return ApiResponse::success(null, 'Product deleted successfully.');
    }

    /**
     * Restock a specific product.
     *
     * @group Products
     *
     * @authenticated
     *
     * @urlParam id int required Example: 1
     *
     * @bodyParam quantity integer required Example: 10
     *
     * @response 200 {
     *     "data": {
     *         "id": "uuid",
     *         "product_name": "Product Name",
     *         "description": "Product Description",
     *         "price": "100.00",
     *         "stock_quantity": 20,
     *         "created_at": "datetime",
     *         "updated_at": "datetime"
     *     },
     *     "message": "Product restocked successfully.",
     *     "status": "success",
     *     "status_code": 200
     * }
     * @response 404 {
     *     "message": "Product not found.",
     *     "status": "error",
     *     "status_code": 404,
     *     "errors": [],
     *     "code": "NOT_FOUND"
     * }
     */
    public function restock(RestockProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->restock($product, $request->validated());

        return ApiResponse::success(new ProductResource($product), 'Product restocked successfully.');
    }
}
