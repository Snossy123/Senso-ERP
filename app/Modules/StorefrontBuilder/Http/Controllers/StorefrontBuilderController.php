<?php

namespace App\Modules\StorefrontBuilder\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Modules\StorefrontBuilder\Http\Requests\RollbackStorefrontRequest;
use App\Modules\StorefrontBuilder\Http\Requests\UpdatePageSectionsRequest;
use App\Modules\StorefrontBuilder\Http\Requests\UpdateStorefrontRequest;
use App\Modules\StorefrontBuilder\Services\LayoutSlotService;
use App\Modules\StorefrontBuilder\Services\StorefrontBuilderService;
use App\Modules\StorefrontBuilder\Services\TemplateRegistryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontBuilderController extends Controller
{
    public function __construct(
        private readonly StorefrontBuilderService $builderService,
        private readonly TemplateRegistryService $templateRegistry,
        private readonly LayoutSlotService $layoutSlotService
    ) {}

    public function index(Request $request): View
    {
        $storefront = $this->builderService->getOrCreateDefaultStorefront();
        $activePageType = (string) $request->query('page', 'home');

        return view('erp.storefront-builder.index', [
            'storefront' => $storefront,
            'templates' => $this->templateRegistry->allHomeTemplates(),
            'navbarChoices' => $this->templateRegistry->navbarComponentChoices(),
            'pageTypes' => $this->templateRegistry->pageTypes(),
            'activePageType' => $activePageType,
        ]);
    }

    public function update(UpdateStorefrontRequest $request): RedirectResponse
    {
        $storefront = $this->builderService->getOrCreateDefaultStorefront();
        $data = $request->validated();

        $incomingSettings = $data['settings'] ?? null;
        unset($data['settings']);

        $storefront->update($data);

        if (is_array($incomingSettings)) {
            $mergedSettings = $this->layoutSlotService->mergeStorefrontSettings(
                $storefront->settings ?? [],
                $incomingSettings
            );
            $storefront->update(['settings' => $mergedSettings]);
        }

        $storefront->update(['status' => 'draft']);
        $this->builderService->forgetRenderCache($storefront->fresh(), null);

        Activity::log(
            'storefront_builder',
            'settings_update',
            'Updated storefront builder settings',
            [
                'active_template_key' => $storefront->fresh()->active_template_key,
            ],
            $storefront->fresh()
        );

        return redirect()
            ->route('admin.storefront-builder.index', ['page' => (string) $request->input('return_page', $request->query('page', 'home'))])
            ->with('success', 'Storefront settings updated.');
    }

    public function updateSections(UpdatePageSectionsRequest $request): RedirectResponse
    {
        $storefront = $this->builderService->getOrCreateDefaultStorefront();
        $data = $request->validated();
        $this->builderService->updatePageSections($storefront, $data['page_type'], $data['sections']);

        Activity::log(
            'storefront_builder',
            'sections_update',
            "Updated storefront sections for {$data['page_type']}",
            [
                'page_type' => $data['page_type'],
                'sections' => count($data['sections']),
            ],
            $storefront->fresh()
        );

        return redirect()
            ->route('admin.storefront-builder.index', ['page' => $data['page_type']])
            ->with('success', 'Page sections updated.');
    }

    public function publish(Request $request): RedirectResponse
    {
        $storefront = $this->builderService->getOrCreateDefaultStorefront();
        $errors = $this->builderService->validatePublishReadiness($storefront);
        if ($errors !== []) {
            return redirect()
                ->route('admin.storefront-builder.index', ['page' => (string) $request->input('return_page', $request->query('page', 'home'))])
                ->with('error', implode(' ', $errors));
        }

        $published = $this->builderService->publish($storefront, $request->user()?->id);

        Activity::log(
            'storefront_builder',
            'publish',
            "Published storefront v{$published->version}",
            [
                'version' => $published->version,
                'published_version_id' => $published->id,
            ],
            $storefront->fresh()
        );

        return redirect()
            ->route('admin.storefront-builder.index', ['page' => (string) $request->input('return_page', $request->query('page', 'home'))])
            ->with('success', 'Storefront published.');
    }

    public function rollback(RollbackStorefrontRequest $request): RedirectResponse
    {
        $storefront = $this->builderService->getOrCreateDefaultStorefront();
        $version = (int) $request->validated('version');
        $this->builderService->rollback($storefront, $version);

        Activity::log(
            'storefront_builder',
            'rollback',
            "Rolled back storefront to v{$version}",
            [
                'version' => $version,
            ],
            $storefront->fresh()
        );

        return redirect()
            ->route('admin.storefront-builder.index', ['page' => (string) $request->input('return_page', $request->query('page', 'home'))])
            ->with('success', 'Storefront rolled back.');
    }

    public function preview(Request $request): View
    {
        $storefront = $this->builderService->getOrCreateDefaultStorefront();
        $pageType = (string) $request->get('page_type', 'home');

        $page = $storefront->pages->firstWhere('page_type', $pageType);

        $uomoRel = $this->templateRegistry->staticReferencePathForPageType(
            $pageType,
            $storefront->active_template_key
        );

        return view('erp.storefront-builder.preview', [
            'storefront' => $storefront,
            'pageType' => $pageType,
            'sections' => $page?->sections ?? collect(),
            'uomoVisualUrl' => url($this->templateRegistry->uomoAssetUrl($uomoRel)),
            'uomoVisualPath' => $uomoRel,
        ]);
    }
}
