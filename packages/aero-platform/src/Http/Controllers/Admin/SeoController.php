<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Models\PlatformPage;
use Aero\Platform\Models\PlatformSetting;
use Aero\Platform\Services\Marketing\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * SEO Controller
 *
 * Manages SEO settings, platform pages, and sitemap.
 */
class SeoController extends Controller
{
    public function __construct(
        protected SeoService $seoService
    ) {}

    /**
     * Display SEO settings page.
     */
    public function index(): Response
    {
        $settings = PlatformSetting::current();

        return Inertia::render('Platform/Admin/Seo/Index', [
            'title' => 'SEO Settings',
            'seoSettings' => $settings->getSeoSettings(),
            'analyticsIntegrations' => $settings->getAnalyticsIntegrations(),
            'pages' => $this->seoService->getPagesWithSeoStatus(),
        ]);
    }

    /**
     * Update SEO settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'default_meta_title' => 'nullable|string|max:60',
            'default_meta_description' => 'nullable|string|max:160',
            'default_meta_keywords' => 'nullable|string|max:255',
            'canonical_domain' => 'nullable|url|max:255',
            'sitemap_enabled' => 'boolean',
            'robots_txt' => 'nullable|string|max:5000',
            'og_site_name' => 'nullable|string|max:100',
            'og_default_image' => 'nullable|url|max:500',
            'twitter_site' => 'nullable|string|max:50',
            'twitter_creator' => 'nullable|string|max:50',
            'schema_organization' => 'nullable|array',
        ]);

        $settings = $this->seoService->updateSeoSettings($validated);

        return response()->json([
            'success' => true,
            'message' => 'SEO settings updated successfully.',
            'data' => $settings->getSeoSettings(),
        ]);
    }

    /**
     * Update analytics integrations.
     */
    public function updateAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'google_analytics' => 'nullable|array',
            'google_analytics.enabled' => 'boolean',
            'google_analytics.measurement_id' => 'nullable|string|max:50',
            'google_tag_manager' => 'nullable|array',
            'google_tag_manager.enabled' => 'boolean',
            'google_tag_manager.container_id' => 'nullable|string|max:50',
            'facebook_pixel' => 'nullable|array',
            'facebook_pixel.enabled' => 'boolean',
            'facebook_pixel.pixel_id' => 'nullable|string|max:50',
            'hotjar' => 'nullable|array',
            'hotjar.enabled' => 'boolean',
            'hotjar.site_id' => 'nullable|string|max:50',
            'mixpanel' => 'nullable|array',
            'mixpanel.enabled' => 'boolean',
            'mixpanel.token' => 'nullable|string|max:100',
        ]);

        $settings = $this->seoService->updateAnalyticsIntegrations($validated);

        return response()->json([
            'success' => true,
            'message' => 'Analytics integrations updated successfully.',
            'data' => $settings->getAnalyticsIntegrations(),
        ]);
    }

    /**
     * List platform pages.
     */
    public function pages(): Response
    {
        $pages = PlatformPage::orderBy('priority')->paginate(20);

        return Inertia::render('Platform/Admin/Seo/Pages', [
            'title' => 'Platform Pages',
            'pages' => $pages,
            'pageTypes' => PlatformPage::getPageTypes(),
        ]);
    }

    /**
     * Store a new platform page.
     */
    public function storePage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:platform_pages,slug',
            'title' => 'required|string|max:255',
            'page_type' => 'required|string|in:'.implode(',', array_keys(PlatformPage::getPageTypes())),
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:95',
            'og_description' => 'nullable|string|max:200',
            'og_image' => 'nullable|url|max:500',
            'twitter_title' => 'nullable|string|max:70',
            'twitter_description' => 'nullable|string|max:200',
            'content' => 'nullable|array',
            'priority' => 'integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $page = PlatformPage::create($validated);

        // Clear sitemap cache
        $this->seoService->clearSitemapCache();

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully.',
            'data' => $page,
        ], 201);
    }

    /**
     * Update a platform page.
     */
    public function updatePage(Request $request, PlatformPage $page): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:platform_pages,slug,'.$page->id,
            'title' => 'required|string|max:255',
            'page_type' => 'required|string|in:'.implode(',', array_keys(PlatformPage::getPageTypes())),
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:95',
            'og_description' => 'nullable|string|max:200',
            'og_image' => 'nullable|url|max:500',
            'twitter_title' => 'nullable|string|max:70',
            'twitter_description' => 'nullable|string|max:200',
            'content' => 'nullable|array',
            'priority' => 'integer|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $page->update($validated);

        // Clear sitemap cache
        $this->seoService->clearSitemapCache();

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully.',
            'data' => $page->fresh(),
        ]);
    }

    /**
     * Delete a platform page.
     */
    public function destroyPage(PlatformPage $page): JsonResponse
    {
        $page->delete();

        // Clear sitemap cache
        $this->seoService->clearSitemapCache();

        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully.',
        ]);
    }

    /**
     * Generate and return sitemap.
     */
    public function sitemap(): \Illuminate\Http\Response
    {
        $content = $this->seoService->generateSitemap();

        return response($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Generate and return robots.txt.
     */
    public function robots(): \Illuminate\Http\Response
    {
        $content = $this->seoService->generateRobotsTxt();

        return response($content, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Validate SEO meta tags for a page.
     */
    public function validateMeta(Request $request): JsonResponse
    {
        $meta = $request->validate([
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'og' => 'nullable|array',
        ]);

        $issues = $this->seoService->validateMetaTags($meta);

        return response()->json([
            'success' => true,
            'issues' => $issues,
            'score' => 100 - (count($issues) * 10),
        ]);
    }

    /**
     * Regenerate sitemap cache.
     */
    public function regenerateSitemap(): JsonResponse
    {
        $this->seoService->clearSitemapCache();
        $sitemap = $this->seoService->generateSitemap();

        return response()->json([
            'success' => true,
            'message' => 'Sitemap regenerated successfully.',
            'size' => strlen($sitemap),
        ]);
    }
}
