<?php

namespace Aero\Core\Services;

use Aero\Core\Models\RetentionPolicy;

/**
 * Retention Policy Service
 *
 * Core business logic for managing and executing data retention policies.
 */
class RetentionPolicyService
{
    /**
     * Get all retention policies for a tenant.
     */
    public function getPolicies(): \Illuminate\Database\Eloquent\Collection
    {
        return RetentionPolicy::where('tenant_id', tenant('id'))->get();
    }

    /**
     * Get active retention policies.
     */
    public function getActivePolicies(): \Illuminate\Database\Eloquent\Collection
    {
        return RetentionPolicy::where('tenant_id', tenant('id'))
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get policies due to run.
     */
    public function getDuePolicies(): \Illuminate\Database\Eloquent\Collection
    {
        return RetentionPolicy::where('tenant_id', tenant('id'))
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            })
            ->get();
    }

    /**
     * Create a new retention policy.
     */
    public function createPolicy(array $data): RetentionPolicy
    {
        $policy = RetentionPolicy::create([
            'tenant_id' => tenant('id'),
            'entity_type' => $data['entity_type'],
            'action' => $data['action'],
            'retention_days' => $data['retention_days'],
            'filters' => $data['filters'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'schedule' => $data['schedule'] ?? 'daily',
            'notes' => $data['notes'] ?? null,
        ]);

        $policy->calculateNextRun();
        $policy->save();

        return $policy;
    }

    /**
     * Update a retention policy.
     */
    public function updatePolicy(RetentionPolicy $policy, array $data): RetentionPolicy
    {
        $policy->update($data);
        
        if (isset($data['schedule'])) {
            $policy->calculateNextRun();
            $policy->save();
        }

        return $policy;
    }

    /**
     * Delete a retention policy.
     */
    public function deletePolicy(RetentionPolicy $policy): void
    {
        $policy->delete();
    }

    /**
     * Execute a retention policy.
     */
    public function executePolicy(RetentionPolicy $policy): array
    {
        $recordsProcessed = 0;
        $errors = [];

        try {
            // Special handling for trash entity type
            if ($policy->entity_type === 'trash') {
                return $this->executeTrashPolicy($policy);
            }

            $query = $policy->getEntityQuery();

            if (! $query) {
                throw new \InvalidArgumentException("Unknown entity type: {$policy->entity_type}");
            }

            $cutoffDate = now()->subDays($policy->retention_days);

            if ($policy->action === 'delete') {
                $records = $query->where('created_at', '<', $cutoffDate)->get();
                
                foreach ($records as $record) {
                    $record->delete();
                    $recordsProcessed++;
                }
            } elseif ($policy->action === 'archive') {
                $records = $query->where('created_at', '<', $cutoffDate)->get();
                
                foreach ($records as $record) {
                    $record->update(['archived_at' => now()]);
                    $recordsProcessed++;
                }
            }

            $policy->update([
                'last_run_at' => now(),
                'records_processed' => $recordsProcessed,
            ]);

            $policy->calculateNextRun();
            $policy->save();

        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        return [
            'records_processed' => $recordsProcessed,
            'errors' => $errors,
        ];
    }

    /**
     * Execute trash retention policy (empty trash for entities).
     */
    protected function executeTrashPolicy(RetentionPolicy $policy): array
    {
        $recordsProcessed = 0;
        $errors = [];
        $trashService = app(TrashService::class);

        try {
            $cutoffDate = now()->subDays($policy->retention_days);
            $entityModels = $trashService->getEntityModels();

            foreach ($entityModels as $entity => $modelClass) {
                // Force delete trashed items older than retention days
                $count = $modelClass::onlyTrashed()
                    ->where('deleted_at', '<', $cutoffDate)
                    ->forceDelete();
                $recordsProcessed += $count;
            }

            $policy->update([
                'last_run_at' => now(),
                'records_processed' => $recordsProcessed,
            ]);

            $policy->calculateNextRun();
            $policy->save();

        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        return [
            'records_processed' => $recordsProcessed,
            'errors' => $errors,
        ];
    }

    /**
     * Get available entity types for retention policies.
     */
    public function getEntityTypes(): array
    {
        return [
            'audit_logs' => 'Audit Logs',
            'activities' => 'Activities',
            'data_exports' => 'Data Exports',
            'trash' => 'Trash (Auto-empty)',
        ];
    }

    /**
     * Get available actions for retention policies.
     */
    public function getActions(): array
    {
        return [
            'delete' => 'Delete',
            'archive' => 'Archive',
        ];
    }

    /**
     * Get available schedules for retention policies.
     */
    public function getSchedules(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
        ];
    }
}
