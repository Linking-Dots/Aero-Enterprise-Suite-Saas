<?php

use Aero\I18n\Http\Controllers\LanguageController;
use Aero\I18n\Http\Controllers\LocaleController;
use Aero\I18n\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| I18n Routes
|--------------------------------------------------------------------------
|
| Locale switching endpoint and translation management. Registered inside the web middleware group
| by AeroI18nServiceProvider so session and CSRF are available.
|
*/

// Locale switching (existing)
Route::post('/locale', [LocaleController::class, 'update'])
    ->name('i18n.locale.update');

Route::get('/locale', [LocaleController::class, 'index'])
    ->name('i18n.locale.index');

Route::get('/translations/{namespace?}', [LocaleController::class, 'translations'])
    ->name('i18n.translations');

// Translation management (new)
Route::middleware('auth:web')->group(function () {
    Route::prefix('i18n')->name('i18n.')->group(function () {
        // Language management
        Route::middleware('hrmac:i18n.translations.languages.view')->group(function () {
            Route::get('/languages', [LanguageController::class, 'index'])
                ->name('languages.index');
            Route::put('/languages/{code}', [LanguageController::class, 'update'])
                ->middleware('hrmac:i18n.translations.languages.enable')
                ->name('languages.update');
        });

        // Translation editor
        Route::middleware('hrmac:i18n.translations.translation_editor.view')->group(function () {
            Route::get('/translations', [TranslationController::class, 'index'])
                ->name('translations.index');
            Route::put('/translations/{id}', [TranslationController::class, 'update'])
                ->middleware('hrmac:i18n.translations.translation_editor.update')
                ->name('translations.update');
            Route::post('/translations/auto-translate', [TranslationController::class, 'autoTranslate'])
                ->middleware('hrmac:i18n.translations.translation_editor.auto_translate')
                ->name('translations.auto-translate');
            Route::post('/translations/import', [TranslationController::class, 'import'])
                ->middleware('hrmac:i18n.translations.translation_editor.import')
                ->name('translations.import');
            Route::get('/translations/export', [TranslationController::class, 'export'])
                ->middleware('hrmac:i18n.translations.translation_editor.export')
                ->name('translations.export');
        });
    });
});
