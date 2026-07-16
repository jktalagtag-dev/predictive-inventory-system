<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Services\CategoryException;
use App\Domains\Catalog\Services\CategoryService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCategoryRequest;
use App\Http\Requests\Api\V1\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $perPage = min(max((int) $request->integer('perPage', 20), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $query = Category::query()->with('parent');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('parentId')) {
            $parentId = $request->query('parentId');
            if ($parentId === 'null') {
                $query->whereNull('parent_category_id');
            } else {
                $query->where('parent_category_id', (int) $parentId);
            }
        }

        if ($request->filled('isActive')) {
            $query->where('is_active', $request->boolean('isActive'));
        }

        $paginator = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => CategoryResource::collection($paginator->items()),
            'meta' => [
                'requestId' => (string) Str::uuid(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $category = $this->categoryService->create([
                'parent_category_id' => $validated['parentCategoryId'] ?? null,
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['isActive'] ?? true,
            ], $request->user());
        } catch (CategoryException $exception) {
            return $this->exceptionResponse($exception);
        }

        return (new CategoryResource($category->load('parent')))->response()->setStatusCode(201);
    }

    public function show(Category $category): CategoryResource
    {
        $this->authorize('view', $category);

        return new CategoryResource($category->load('parent'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource|JsonResponse
    {
        $validated = $request->validated();

        if ((int) $validated['version'] !== $category->row_version) {
            return $this->conflictResponse('This category was changed by someone else. Reload and try again.');
        }

        $attributes = [];
        if (array_key_exists('parentCategoryId', $validated)) {
            $attributes['parent_category_id'] = $validated['parentCategoryId'];
        }
        foreach (['code', 'name', 'description', 'isActive'] as $field) {
            if (array_key_exists($field, $validated)) {
                $attributes[$field === 'isActive' ? 'is_active' : $field] = $validated[$field];
            }
        }

        try {
            $updated = $this->categoryService->update($category, $attributes, $request->user());
        } catch (CategoryException $exception) {
            return $this->exceptionResponse($exception);
        }

        return new CategoryResource($updated->load('parent'));
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $version = (int) $request->query('version', -1);
        if ($version !== $category->row_version) {
            return $this->conflictResponse('This category was changed by someone else. Reload and try again.');
        }

        $this->categoryService->archive($category, $request->user());

        return response()->json(['data' => ['archived' => true]]);
    }

    private function exceptionResponse(CategoryException $exception): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $exception->errorCode(),
                'message' => $exception->getMessage(),
                'requestId' => (string) Str::uuid(),
            ],
        ], $exception->status());
    }

    private function conflictResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => ['code' => 'VERSION_CONFLICT', 'message' => $message, 'requestId' => (string) Str::uuid()],
        ], 409);
    }
}
