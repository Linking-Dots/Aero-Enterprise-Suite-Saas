<?php

use Aero\Forms\Http\Controllers\FormController;
use Aero\Forms\Http\Controllers\FormSubmissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Forms Routes
|--------------------------------------------------------------------------
|
| Routes for form builder, forms, and form submissions.
|
*/

Route::middleware('auth:web')->group(function () {
    // Forms
    Route::prefix('forms')->middleware('hrmac:forms.forms.view')->group(function () {
        Route::get('/', [FormController::class, 'index'])
            ->name('core.forms.index');
        Route::get('/create', [FormController::class, 'create'])
            ->middleware('hrmac:forms.forms.create')
            ->name('core.forms.create');
        Route::post('/', [FormController::class, 'store'])
            ->middleware('hrmac:forms.forms.create')
            ->name('core.forms.store');
        Route::get('/{form}/edit', [FormController::class, 'edit'])
            ->middleware('hrmac:forms.forms.update')
            ->name('core.forms.edit');
        Route::put('/{form}', [FormController::class, 'update'])
            ->middleware('hrmac:forms.forms.update')
            ->name('core.forms.update');
        Route::post('/{form}/publish', [FormController::class, 'publish'])
            ->middleware('hrmac:forms.forms.publish')
            ->name('core.forms.publish');
        Route::post('/{form}/unpublish', [FormController::class, 'unpublish'])
            ->middleware('hrmac:forms.forms.unpublish')
            ->name('core.forms.unpublish');
        Route::delete('/{form}', [FormController::class, 'destroy'])
            ->middleware('hrmac:forms.forms.delete')
            ->name('core.forms.destroy');

        // Submissions
        Route::get('/{form}/submissions', [FormSubmissionController::class, 'index'])
            ->middleware('hrmac:forms.submissions.view')
            ->name('core.forms.submissions.index');
        Route::get('/{form}/submissions/{submission}', [FormSubmissionController::class, 'show'])
            ->middleware('hrmac:forms.submissions.view')
            ->name('core.forms.submissions.show');
        Route::put('/{form}/submissions/{submission}', [FormSubmissionController::class, 'update'])
            ->middleware('hrmac:forms.submissions.update')
            ->name('core.forms.submissions.update');
        Route::delete('/{form}/submissions/{submission}', [FormSubmissionController::class, 'destroy'])
            ->middleware('hrmac:forms.submissions.delete')
            ->name('core.forms.submissions.destroy');
        Route::get('/{form}/submissions/export', [FormSubmissionController::class, 'export'])
            ->middleware('hrmac:forms.submissions.export')
            ->name('core.forms.submissions.export');
    });

    // Form Templates
    Route::prefix('form-templates')->middleware('hrmac:forms.templates.view')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'Form templates list']);
        })->name('core.form-templates.index');
        Route::post('/', function () {
            return response()->json(['message' => 'Create form template']);
        })->middleware('hrmac:forms.templates.create')
            ->name('core.form-templates.store');
    });
});
