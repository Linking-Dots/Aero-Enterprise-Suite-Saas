<?php

use Aero\Workflow\Http\Controllers\WorkflowController;
use Aero\Workflow\Http\Controllers\WorkflowInstanceController;
use Aero\Workflow\Http\Controllers\WorkflowTemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Workflow Engine Routes
|--------------------------------------------------------------------------
|
| Routes for workflow definitions, instances, approvals, and templates.
|
*/

Route::middleware('auth:web')->group(function () {
    // Workflow Definitions
    Route::prefix('workflows')->middleware('hrmac:workflow.workflows.definitions.view')->group(function () {
        Route::get('/', [WorkflowController::class, 'index'])->name('workflows.index');
        Route::post('/', [WorkflowController::class, 'store'])
            ->middleware('hrmac:workflow.workflows.definitions.create')
            ->name('workflows.store');
        Route::get('/{workflow}', [WorkflowController::class, 'show'])->name('workflows.show');
        Route::put('/{workflow}', [WorkflowController::class, 'update'])
            ->middleware('hrmac:workflow.workflows.definitions.update')
            ->name('workflows.update');
        Route::delete('/{workflow}', [WorkflowController::class, 'destroy'])
            ->middleware('hrmac:workflow.workflows.definitions.delete')
            ->name('workflows.destroy');
        Route::post('/{workflow}/activate', [WorkflowController::class, 'activate'])
            ->middleware('hrmac:workflow.workflows.definitions.activate')
            ->name('workflows.activate');
        Route::post('/{workflow}/deactivate', [WorkflowController::class, 'deactivate'])
            ->middleware('hrmac:workflow.workflows.definitions.deactivate')
            ->name('workflows.deactivate');
    });

    // Workflow Instances & Approvals
    Route::prefix('workflow-instances')->group(function () {
        Route::middleware('hrmac:workflow.workflows.instances.view')->group(function () {
            Route::get('/', [WorkflowInstanceController::class, 'index'])->name('workflow-instances.index');
            Route::get('/{instance}', [WorkflowInstanceController::class, 'show'])->name('workflow-instances.show');
            Route::post('/{instance}/retry', [WorkflowInstanceController::class, 'retry'])
                ->middleware('hrmac:workflow.workflows.instances.retry')
                ->name('workflow-instances.retry');
        });

        Route::middleware('hrmac:workflow.workflows.approvals.view')->group(function () {
            Route::get('/approvals', [WorkflowInstanceController::class, 'approvals'])->name('workflow-instances.approvals');
            Route::post('/{instance}/approve', [WorkflowInstanceController::class, 'approve'])
                ->middleware('hrmac:workflow.workflows.approvals.approve')
                ->name('workflow-instances.approve');
            Route::post('/{instance}/reject', [WorkflowInstanceController::class, 'reject'])
                ->middleware('hrmac:workflow.workflows.approvals.reject')
                ->name('workflow-instances.reject');
            Route::post('/{instance}/escalate', [WorkflowInstanceController::class, 'escalate'])
                ->middleware('hrmac:workflow.workflows.approvals.escalate')
                ->name('workflow-instances.escalate');
        });
    });

    // Workflow Templates
    Route::prefix('workflow-templates')->middleware('hrmac:workflow.workflows.templates.view')->group(function () {
        Route::get('/', [WorkflowTemplateController::class, 'index'])->name('workflow-templates.index');
        Route::post('/', [WorkflowTemplateController::class, 'store'])
            ->middleware('hrmac:workflow.workflows.templates.create')
            ->name('workflow-templates.store');
        Route::get('/{template}', [WorkflowTemplateController::class, 'show'])->name('workflow-templates.show');
        Route::put('/{template}', [WorkflowTemplateController::class, 'update'])
            ->middleware('hrmac:workflow.workflows.templates.update')
            ->name('workflow-templates.update');
        Route::delete('/{template}', [WorkflowTemplateController::class, 'destroy'])
            ->middleware('hrmac:workflow.workflows.templates.delete')
            ->name('workflow-templates.destroy');
    });
});
