<?php

namespace Aero\I18n\Http\Controllers;

use Aero\I18n\Services\TranslationManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TranslationController extends Controller
{
    public function __construct(
        protected TranslationManagementService $service,
    ) {}

    public function index(Request $request): Response
    {
        $languageCode = $request->input('language', 'en');
        $namespace = $request->input('namespace');
        $group = $request->input('group');

        $translations = $this->service->getTranslations($languageCode, $namespace, $group);
        $missingTranslations = $this->service->detectMissingTranslations($languageCode);

        return Inertia::render('I18n/Editor/Index', [
            'languageCode' => $languageCode,
            'namespace' => $namespace,
            'group' => $group,
            'translations' => $translations,
            'missingTranslations' => $missingTranslations,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string'],
        ]);

        $translation = $this->service->updateTranslation($id, $validated['value']);

        return response()->json([
            'success' => true,
            'translation' => $translation,
        ]);
    }

    public function autoTranslate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language_code' => ['required', 'string'],
            'keys' => ['required', 'array'],
        ]);

        $results = $this->service->autoTranslate(
            $validated['language_code'],
            $validated['keys']
        );

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language_code' => ['required', 'string'],
            'data' => ['required', 'array'],
            'format' => ['nullable', 'string', 'in:json'],
        ]);

        $count = $this->service->importTranslations(
            $validated['language_code'],
            $validated['data'],
            $validated['format'] ?? 'json'
        );

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "Imported {$count} translations",
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language_code' => ['required', 'string'],
            'format' => ['nullable', 'string', 'in:json'],
        ]);

        $data = $this->service->exportTranslations(
            $validated['language_code'],
            $validated['format'] ?? 'json'
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
