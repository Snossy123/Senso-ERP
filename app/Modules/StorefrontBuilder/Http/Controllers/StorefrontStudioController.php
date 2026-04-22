<?php

namespace App\Modules\StorefrontBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Modules\StorefrontBuilder\Http\Requests\UpdatePageLayoutRequest;
use App\Modules\StorefrontBuilder\Models\StorefrontPublishVersion;
use App\Modules\StorefrontBuilder\Services\PageSchemaService;
use App\Modules\StorefrontBuilder\Services\StorefrontBuilderService;
use App\Modules\StorefrontBuilder\Services\TemplateRegistryService;
use App\Modules\StorefrontBuilder\Services\UomoFragmentService;
use App\Services\TenantManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class StorefrontStudioController extends Controller
{
    public function __construct(
        private readonly StorefrontBuilderService $builderService,
        private readonly PageSchemaService $pageSchemaService,
        private readonly TemplateRegistryService $templateRegistry,
        private readonly UomoFragmentService $uomoFragments
    ) {
    }

    public function index(Request $request): View
    {
        $pageType = (string) $request->query('page', 'home');

        return view('erp.storefront-studio.index', [
            'initialPageType' => $pageType,
            'pageTypes' => $this->templateRegistry->pageTypes(),
            'storePreviewUrl' => url('/store'),
        ]);
    }

    public function showPageLayout(string $pageType): JsonResponse
    {
        $this->assertPageType($pageType);
        $storefront = $this->builderService->getOrCreateDefaultStorefront();
        $page = $storefront->pages()->where('page_type', $pageType)->with('sections')->firstOrFail();

        return response()->json([
            'page_type' => $pageType,
            'layout_schema' => $this->pageSchemaService->resolvedSchema($page),
        ]);
    }

    public function updatePageLayout(UpdatePageLayoutRequest $request, string $pageType): JsonResponse
    {
        $this->assertPageType($pageType);
        $storefront = $this->builderService->getOrCreateDefaultStorefront();
        $page = $storefront->pages()->where('page_type', $pageType)->firstOrFail();

        $this->pageSchemaService->saveSchema($page, $request->validated('layout_schema'));
        $storefront->update(['status' => 'draft']);
        $this->forgetStudioCatalogCache((int) app(TenantManager::class)->getCurrentId());
        $this->builderService->forgetRenderCache($storefront->fresh(), null);

        return response()->json([
            'ok' => true,
            'page_type' => $pageType,
            'layout_schema' => $this->pageSchemaService->resolvedSchema($page->fresh(['sections'])),
        ]);
    }

    public function catalogProducts(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 24)));
        $tenantId = (int) app(TenantManager::class)->getCurrentId();
        $page = max(1, (int) $request->query('page', 1));
        $cacheKey = "storefront-studio:catalog:products:{$tenantId}:{$perPage}:{$page}";

        $payload = Cache::remember($cacheKey, 45, function () use ($perPage, $page) {
            $paginator = Product::query()
                ->where('is_ecommerce', true)
                ->where('is_active', true)
                ->with('category')
                ->orderByDesc('id')
                ->paginate($perPage, ['*'], 'page', $page);

            return [
                'data' => $paginator->getCollection()->map(fn(Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'sku' => $p->sku,
                    'selling_price' => (float) $p->selling_price,
                    'stock_quantity' => (int) $p->stock_quantity,
                    'image' => $p->image ? asset('storage/' . $p->image) : null,
                    'category' => $p->category?->name,
                ])->values()->all(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total' => $paginator->total(),
                ],
            ];
        });

        return response()->json($payload);
    }

    public function catalogCategories(): JsonResponse
    {
        $tenantId = (int) app(TenantManager::class)->getCurrentId();
        $cacheKey = "storefront-studio:catalog:categories:{$tenantId}";

        $categories = Cache::remember($cacheKey, 120, function () {
            return Category::query()
                ->whereHas('products', fn($q) => $q->where('is_ecommerce', true)->where('is_active', true))
                ->orderBy('name')
                ->get(['id', 'name']);
        });

        return response()->json(['data' => $categories]);
    }

    /** Read-only cart summary for studio bindings (same session as /store cart). */
    public function catalogCartSummary(): JsonResponse
    {
        $cart = session('cart', []);
        $lines = [];
        $subtotal = 0.0;

        foreach ($cart as $productId => $item) {
            $product = Product::query()->find((int) $productId);
            if (!$product || !$product->is_ecommerce || !$product->is_active) {
                continue;
            }
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $line = (float) $product->selling_price * $qty;
            $subtotal += $line;
            $lines[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'qty' => $qty,
                'line_total' => $line,
            ];
        }

        return response()->json([
            'lines' => $lines,
            'subtotal' => $subtotal,
            'count' => count($lines),
        ]);
    }

    public function uomoPresets(): JsonResponse
    {
        return response()->json([
            'navbar_fragments' => $this->uomoFragments->listNavbarPresets(),
        ]);
    }

    public function pageLayoutDiff(string $pageType): JsonResponse
    {
        $this->assertPageType($pageType);
        $storefront = $this->builderService->getOrCreateDefaultStorefront();
        $page = $storefront->pages()->where('page_type', $pageType)->with('sections')->firstOrFail();
        $draft = $this->pageSchemaService->resolvedSchema($page);
        $draftDigest = hash('sha256', json_encode($draft));

        $publishedDigest = null;
        if ($storefront->published_version_id) {
            /** @var StorefrontPublishVersion|null $version */
            $version = StorefrontPublishVersion::query()->find($storefront->published_version_id);
            if ($version && is_array($version->snapshot)) {
                $snapPage = collect($version->snapshot['pages'] ?? [])->firstWhere('page_type', $pageType);
                if (is_array($snapPage) && array_key_exists('layout_schema', $snapPage)) {
                    $publishedDigest = hash('sha256', json_encode($snapPage['layout_schema']));
                }
            }
        }

        return response()->json([
            'page_type' => $pageType,
            'draft_digest' => $draftDigest,
            'published_digest' => $publishedDigest,
            'dirty' => $publishedDigest !== null && $publishedDigest !== $draftDigest,
        ]);
    }

    public function importPageLayout(UpdatePageLayoutRequest $request, string $pageType): JsonResponse
    {
        return $this->updatePageLayout($request, $pageType);
    }

    private function assertPageType(string $pageType): void
    {
        if (!in_array($pageType, $this->templateRegistry->pageTypes(), true)) {
            abort(404, 'Unknown page type.');
        }
    }

    private function forgetStudioCatalogCache(int $tenantId): void
    {
        foreach ([8, 12, 24, 48, 100] as $perPage) {
            for ($page = 1; $page <= 20; $page++) {
                Cache::forget("storefront-studio:catalog:products:{$tenantId}:{$perPage}:{$page}");
            }
        }
        Cache::forget("storefront-studio:catalog:categories:{$tenantId}");
    }
}
