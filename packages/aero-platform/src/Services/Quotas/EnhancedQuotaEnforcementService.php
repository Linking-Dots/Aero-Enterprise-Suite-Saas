<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Quotas;

use Aero\Platform\Models\QuotaEnforcementSetting;
use Aero\Platform\Models\QuotaWarning;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\NotificationTemplateService;
use Aero\Notifications\Services\Sms\SmsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced Quota Enforcement Service with Grace Period Support
 *
 * Extends the base QuotaEnforcementService with:
 * - Configurable warning thresholds (default: 80%, 90%, 100%)
 * - Grace period before blocking (default: 10 days)
 * - Progressive warning escalation
 * - Automatic notification dispatch
 */
class EnhancedQuotaEnforcementService extends QuotaEnforcementService
{
    public function __construct(
        protected NotificationTemplateService $notificationService,
        protected SmsService $smsService
    ) {
        parent::__construct();
    }

    /**
     * Check if tenant can create a new resource with grace period enforcement.
     */
    public function canCreateWithGracePeriod(Tenant $tenant, string $quotaType): bool
    {
        $current = $this->getCurrentUsage($tenant, $quotaType);
        $limit = $this->getQuotaLimit($tenant, $quotaType);

        // Unlimited quota
        if ($limit === -1) {
            return true;
        }

        // Under limit - allow
        if ($current < $limit) {
            return true;
        }

        // Get enforcement settings
        $settings = $this->getEnforcementSettings($quotaType);
        $percentage = ($current / $limit) * 100;

        // Check if we're in grace period
        if ($percentage >= $settings->block_threshold_percentage) {
            $firstWarning = $this->getFirstWarningDate($tenant, $quotaType);

            if ($firstWarning) {
                $daysInGrace = now()->diffInDays($firstWarning);

                // Still in grace period - allow but warn
                if ($daysInGrace < $settings->warning_period_days) {
                    $this->sendGracePeriodNotification($tenant, $quotaType, $daysInGrace, $settings->warning_period_days);

                    return true;
                }

                // Grace period expired - block
                Log::warning("Tenant {$tenant->id} exceeded {$quotaType} quota after {$daysInGrace} days grace period");

                return false;
            }

            // First time hitting limit - create warning and allow
            $this->createQuotaWarning($tenant, $quotaType, $percentage);
            $this->sendWarningNotification($tenant, $quotaType, $percentage);

            return true;
        }

        // Send warning notifications at configured thresholds
        if ($percentage >= $settings->warning_threshold_percentage) {
            $this->checkAndSendWarnings($tenant, $quotaType, $percentage);
        }

        return true;
    }

    /**
     * Get enforcement settings for a quota type.
     */
    protected function getEnforcementSettings(string $quotaType): QuotaEnforcementSetting
    {
        return QuotaEnforcementSetting::where('quota_type', $quotaType)
            ->where('is_active', true)
            ->first() ?? $this->getDefaultSettings($quotaType);
    }

    /**
     * Get default enforcement settings if none configured.
     */
    protected function getDefaultSettings(string $quotaType): QuotaEnforcementSetting
    {
        $setting = new QuotaEnforcementSetting;
        $setting->quota_type = $quotaType;
        $setting->warning_threshold_percentage = 80;
        $setting->critical_threshold_percentage = 90;
        $setting->block_threshold_percentage = 100;
        $setting->warning_period_days = 10;
        $setting->is_active = true;

        return $setting;
    }

    /**
     * Get the date of first warning for a tenant and quota type.
     */
    protected function getFirstWarningDate(Tenant $tenant, string $quotaType): ?Carbon
    {
        $warning = QuotaWarning::where('tenant_id', $tenant->id)
            ->where('quota_type', $quotaType)
            ->where('is_dismissed', false)
            ->orderBy('created_at', 'asc')
            ->first();

        return $warning ? Carbon::parse($warning->created_at) : null;
    }

    /**
     * Create a quota warning record.
     */
    protected function createQuotaWarning(Tenant $tenant, string $quotaType, float $percentage): QuotaWarning
    {
        return QuotaWarning::create([
            'tenant_id' => $tenant->id,
            'quota_type' => $quotaType,
            'percentage_used' => $percentage,
            'warning_level' => $this->getWarningLevel($percentage),
            'is_dismissed' => false,
            'expires_at' => now()->addDays($this->getEnforcementSettings($quotaType)->warning_period_days),
        ]);
    }

    /**
     * Determine warning level based on percentage.
     */
    protected function getWarningLevel(float $percentage): string
    {
        if ($percentage >= 100) {
            return 'critical';
        }
        if ($percentage >= 90) {
            return 'high';
        }
        if ($percentage >= 80) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Check and send appropriate warnings based on percentage.
     */
    protected function checkAndSendWarnings(Tenant $tenant, string $quotaType, float $percentage): void
    {
        $settings = $this->getEnforcementSettings($quotaType);

        // Check if we already sent a warning recently
        $recentWarning = QuotaWarning::where('tenant_id', $tenant->id)
            ->where('quota_type', $quotaType)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if (! $recentWarning) {
            $this->createQuotaWarning($tenant, $quotaType, $percentage);
            $this->sendWarningNotification($tenant, $quotaType, $percentage);
        }
    }

    /**
     * Send warning notification via email and SMS.
     */
    protected function sendWarningNotification(Tenant $tenant, string $quotaType, float $percentage): void
    {
        $data = [
            'quota_type' => $quotaType,
            'percentage' => round($percentage, 1),
            'tenant_name' => $tenant->name,
            'limit' => $this->getQuotaLimit($tenant, $quotaType),
            'current' => $this->getCurrentUsage($tenant, $quotaType),
        ];

        try {
            // Send email notification
            $emailHtml = $this->notificationService->renderEmail($tenant, 'quota_warning', $data);
            // TODO: Dispatch email job here

            // Send SMS if percentage is critical (>= 90%)
            if ($percentage >= 90) {
                $smsMessage = $this->notificationService->renderSms($tenant, 'quota_warning', $data);

                // Get admin phone numbers for this tenant
                $adminPhones = $this->getAdminPhoneNumbers($tenant);
                foreach ($adminPhones as $phone) {
                    $this->smsService->send($phone, $smsMessage);
                }
            }

            Log::info("Sent quota warning for tenant {$tenant->id}, quota {$quotaType} at {$percentage}%");
        } catch (\Exception $e) {
            Log::error("Failed to send quota warning: {$e->getMessage()}");
        }
    }

    /**
     * Send grace period notification.
     */
    protected function sendGracePeriodNotification(Tenant $tenant, string $quotaType, int $daysUsed, int $totalDays): void
    {
        $daysRemaining = $totalDays - $daysUsed;

        $data = [
            'quota_type' => $quotaType,
            'days_remaining' => $daysRemaining,
            'days_used' => $daysUsed,
            'total_days' => $totalDays,
            'tenant_name' => $tenant->name,
        ];

        try {
            $emailHtml = $this->notificationService->renderEmail($tenant, 'grace_period_warning', $data);
            // TODO: Dispatch email job

            // Send SMS if less than 3 days remaining
            if ($daysRemaining <= 3) {
                $smsMessage = $this->notificationService->renderSms($tenant, 'grace_period_warning', $data);

                $adminPhones = $this->getAdminPhoneNumbers($tenant);
                foreach ($adminPhones as $phone) {
                    $this->smsService->send($phone, $smsMessage);
                }
            }

            Log::info("Sent grace period warning for tenant {$tenant->id}, {$daysRemaining} days remaining");
        } catch (\Exception $e) {
            Log::error("Failed to send grace period notification: {$e->getMessage()}");
        }
    }

    /**
     * Get admin phone numbers for tenant notifications.
     */
    protected function getAdminPhoneNumbers(Tenant $tenant): array
    {
        // TODO: Implement logic to get super admin phone numbers for this tenant
        // For now, return empty array
        return [];
    }

    /**
     * Dismiss a quota warning.
     */
    public function dismissWarning(int $warningId): bool
    {
        $warning = QuotaWarning::find($warningId);

        if ($warning) {
            $warning->is_dismissed = true;
            $warning->dismissed_at = now();

            return $warning->save();
        }

        return false;
    }

    /**
     * Get active warnings for a tenant.
     */
    public function getActiveWarnings(Tenant $tenant): Collection
    {
        return QuotaWarning::where('tenant_id', $tenant->id)
            ->where('is_dismissed', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get quota usage summary for a tenant.
     */
    public function getQuotaSummary(Tenant $tenant): array
    {
        $summary = [];

        foreach (array_keys($this->defaultQuotas['free']) as $quotaType) {
            $current = $this->getCurrentUsage($tenant, str_replace('max_', '', $quotaType));
            $limit = $this->getQuotaLimit($tenant, str_replace('max_', '', $quotaType));
            $percentage = $limit > 0 ? ($current / $limit) * 100 : 0;

            $summary[$quotaType] = [
                'current' => $current,
                'limit' => $limit,
                'percentage' => round($percentage, 1),
                'status' => $this->getQuotaStatus($percentage),
                'warning_level' => $this->getWarningLevel($percentage),
            ];
        }

        return $summary;
    }

    /**
     * Get quota status based on percentage.
     */
    protected function getQuotaStatus(float $percentage): string
    {
        if ($percentage >= 100) {
            return 'exceeded';
        }
        if ($percentage >= 90) {
            return 'critical';
        }
        if ($percentage >= 80) {
            return 'warning';
        }

        return 'ok';
    }
}
