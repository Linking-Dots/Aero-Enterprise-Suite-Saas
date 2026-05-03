<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function home(Request $request): Response
    {
        return Inertia::render('Platform/Public/Home', [
            'title' => 'Home',
        ]);
    }

    public function pricing(Request $request): Response
    {
        // Load plans from database if available, otherwise use static data
        $plans = [];
        try {
            if (\Schema::hasTable('plans')) {
                $dbPlans = \DB::table('plans')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

                $plans = $dbPlans->map(function ($plan) {
                    return [
                        'id' => $plan->slug,
                        'name' => $plan->name,
                        'tagline' => $plan->description ?? '',
                        'monthlyPrice' => $plan->monthly_price, // Convert cents to dollars
                        'annualPrice' => $plan->yearly_price, // Convert cents to dollars
                        'currency' => '$',
                        'highlight' => $plan->is_featured ?? false,
                        'badge' => $plan->is_featured ? ($plan->tier === 2 ? 'Most Popular' : null) : null,
                        'cta' => 'Start Free Trial',
                        'ctaVariant' => $plan->is_featured ? 'solid' : 'bordered',
                        'accentColor' => $plan->tier === 1 ? 'var(--cyan-aeos, #00E5FF)' : 
                                    ($plan->tier === 2 ? 'var(--indigo-aeos, #6366F1)' : 
                                    ($plan->tier === 3 ? 'var(--amber-aeos, #FFB347)' : 'var(--cyan-aeos, #00E5FF)')),
                        'users' => $this->getUserLimit($plan),
                        'subsidiaries' => $this->getEntityLimit($plan),
                        'modules' => $this->getModuleList($plan),
                        'perks' => $this->getPlanPerks($plan),
                    ];
                })->toArray();
            }
        } catch (\Exception $e) {
            // If database query fails, fall back to empty array (frontend will use static data)
            $plans = [];
        }

        return Inertia::render('Platform/Public/Pricing', [
            'title' => 'Pricing',
            'plans' => $plans,
        ]);
    }

    /**
     * Get user limit description from plan limits
     */
    protected function getUserLimit($plan): string
    {
        $limits = json_decode($plan->limits ?? '{}', true);
        $maxUsers = $limits['max_users'] ?? null;
        
        if ($maxUsers === null) {
            return 'Unlimited users';
        }
        return "Up to {$maxUsers} users";
    }

    /**
     * Get entity/subsidiary limit description from plan limits
     */
    protected function getEntityLimit($plan): string
    {
        $limits = json_decode($plan->limits ?? '{}', true);
        // This is a placeholder - actual entity limit might be stored differently
        return '1 entity'; // Default for now
    }

    /**
     * Get module list from plan features
     */
    protected function getModuleList($plan): array
    {
        $features = json_decode($plan->features ?? '[]', true);
        // Extract module-related features
        return array_filter($features, function ($feature) {
            return !str_contains(strtolower($feature), 'users') && 
                   !str_contains(strtolower($feature), 'storage') &&
                   !str_contains(strtolower($feature), 'support');
        });
    }

    /**
     * Get plan perks from features
     */
    protected function getPlanPerks($plan): array
    {
        $features = json_decode($plan->features ?? '[]', true);
        return $features;
    }

    public function features(Request $request): Response
    {
        return Inertia::render('Platform/Public/Features', [
            'title' => 'Features',
        ]);
    }

    public function enterprise(Request $request): Response
    {
        return Inertia::render('Platform/Public/Enterprise', [
            'title' => 'Enterprise',
        ]);
    }

    public function about(Request $request): Response
    {
        return Inertia::render('Platform/Public/About', [
            'title' => 'About',
        ]);
    }

    public function docs(Request $request): Response
    {
        return Inertia::render('Platform/Public/Docs', [
            'title' => 'Documentation',
        ]);
    }

    public function contact(Request $request): Response
    {
        return Inertia::render('Platform/Public/Contact', [
            'title' => 'Contact Us — aeos365',
        ]);
    }

    public function blog(Request $request): Response
    {
        return Inertia::render('Platform/Public/Blog', [
            'title' => 'Blog',
        ]);
    }

    public function privacy(Request $request): Response
    {
        return Inertia::render('Platform/Public/LegalPrivacy', [
            'title' => 'Privacy Policy',
        ]);
    }

    public function terms(Request $request): Response
    {
        return Inertia::render('Platform/Public/LegalTerms', [
            'title' => 'Terms of Service',
        ]);
    }

    public function cookies(Request $request): Response
    {
        return Inertia::render('Platform/Public/LegalCookies', [
            'title' => 'Cookie Policy',
        ]);
    }

    public function security(Request $request): Response
    {
        return Inertia::render('Platform/Public/LegalSecurity', [
            'title' => 'Security Policy',
        ]);
    }

    public function docsApi(Request $request): Response
    {
        return Inertia::render('Platform/Public/DocsApi', [
            'title' => 'API Documentation',
        ]);
    }
}
