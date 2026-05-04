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

// Workflow Definitions
Route::prefix('workflows')->group(function () {
    Route::get('/', [WorkflowController::class, 'index'])->name('workflows.index');
    Route::post('/', [WorkflowController::class, 'store'])->name('workflows.store');
    Route::get('/{workflow}', [WorkflowController::class, 'show'])->name('workflows.show');
    Route::put('/{workflow}', [WorkflowController::class, 'update'])->name('workflows.update');
    Route::delete('/{workflow}', [WorkflowController::class, 'destroy'])->name('workflows.destroy');
    Route::post('/{workflow}/activate', [WorkflowController::class, 'activate'])->name('workflows.activate');
    Route::post('/{workflow}/deactivate', [WorkflowController::class, 'deactivate'])->name('workflows.deactivate');
});

// Workflow Instances & Approvals
Route::prefix('workflow-instances')->group(function () {
    Route::get('/', [WorkflowInstanceController::class, 'index'])->name('workflow-instances.index');
    Route::get('/approvals', [WorkflowInstanceController::class, 'approvals'])->name('workflow-instances.approvals');
    Route::get('/{instance}', [WorkflowInstanceController::class, 'show'])->name('workflow-instances.show');
    Route::post('/{instance}/approve', [WorkflowInstanceController::class, 'approve'])->name('workflow-instances.approve');
    Route::post('/{instance}/reject', [WorkflowInstanceController::class, 'reject'])->name('workflow-instances.reject');
    Route::post('/{instance}/escalate', [WorkflowInstanceController::class, 'escalate'])->name('workflow-instances.escalate');
    Route::post('/{instance}/retry', [WorkflowInstanceController::class, 'retry'])->name('workflow-instances.retry');
});

// Workflow Templates
Route::prefix('workflow-templates')->group(function () {
    Route::get('/', [WorkflowTemplateController::class, 'index'])->name('workflow-templates.index');
    Route::post('/', [WorkflowTemplateController::class, 'store'])->name('workflow-templates.store');
    Route::get('/{template}', [WorkflowTemplateController::class, 'show'])->name('workflow-templates.show');
    Route::put('/{template}', [WorkflowTemplateController::class, 'update'])->name('workflow-templates.update');
    Route::delete('/{template}', [WorkflowTemplateController::class, 'destroy'])->name('workflow-templates.destroy');
});
