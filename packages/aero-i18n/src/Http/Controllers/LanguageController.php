<?php

namespace Aero\I18n\Http\Controllers;

use Aero\I18n\Models\Language;
use Aero\I18n\Services\TranslationManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    public function __construct(
        protected TranslationManagementService $service,
    ) {}

    public function index(): Response
    {
        $languages = $this->service->getLanguages();

        return Inertia::render('I18n/Languages/Index', [
            'languages' => $languages,
        ]);
    }

    public function update(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
        ]);

        if ($validated['is_enabled']) {
            $language = $this->service->enableLanguage($code);
        } else {
            $language = $this->service->disableLanguage($code);
        }

        return response()->json([
            'success' => true,
            'language' => $language,
        ]);
    }
}
