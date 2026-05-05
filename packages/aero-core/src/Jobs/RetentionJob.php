<?php

namespace Aero\Core\Jobs;

use Aero\Core\Services\RetentionPolicyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetentionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ?int $policyId = null
    ) {}

    public function handle(RetentionPolicyService $retentionPolicyService): void
    {
        if ($this->policyId) {
            $this->executePolicy($retentionPolicyService, $this->policyId);
        } else {
            $this->executeDuePolicies($retentionPolicyService);
        }
    }

    protected function executePolicy(RetentionPolicyService $retentionPolicyService, int $policyId): void
    {
        $policy = \Aero\Core\Models\RetentionPolicy::find($policyId);

        if (! $policy) {
            Log::warning("Retention policy not found: {$policyId}");
            return;
        }

        try {
            $results = $retentionPolicyService->executePolicy($policy);
            
            Log::info("Retention policy executed: {$policy->entity_type}", [
                'policy_id' => $policy->id,
                'records_processed' => $results['records_processed'],
                'errors' => $results['errors'],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to execute retention policy: {$policy->entity_type}", [
                'policy_id' => $policy->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function executeDuePolicies(RetentionPolicyService $retentionPolicyService): void
    {
        $duePolicies = $retentionPolicyService->getDuePolicies();

        foreach ($duePolicies as $policy) {
            try {
                $results = $retentionPolicyService->executePolicy($policy);
                
                Log::info("Retention policy executed: {$policy->entity_type}", [
                    'policy_id' => $policy->id,
                    'records_processed' => $results['records_processed'],
                    'errors' => $results['errors'],
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to execute retention policy: {$policy->entity_type}", [
                    'policy_id' => $policy->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
