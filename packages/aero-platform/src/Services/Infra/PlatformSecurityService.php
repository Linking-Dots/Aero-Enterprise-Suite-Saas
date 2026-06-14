<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Infra;

use Aero\Contracts\AuditServiceInterface;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Platform\Models\Infra\StaffIpAllowlist;
use Aero\Auth\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlatformSecurityService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    /** @return array<int, array<string, mixed>> */
    public function listSessions(): array
    {
        // Platform sessions are stored in the central sessions table.
        // Return active sessions for all landlord users.
        return DB::connection('central')
            ->table('sessions')
            ->orderByDesc('last_activity')
            ->limit(200)
            ->get()
            ->toArray();
    }

    public function forceLogout(string $sessionId, int $actorId): void
    {
        DB::transaction(function () use ($sessionId, $actorId): void {
            DB::connection('central')->table('sessions')->where('id', $sessionId)->delete();

            $this->audit->log(
                event: AuditEventType::STAFF_SESSION_FORCE_LOGOUT->value,
                action: 'logout',
                subject: null,
                description: "Session {$sessionId} force-terminated by actor {$actorId}"
            );
        });
    }

    /** @return array<string, mixed> */
    public function getMfaStatus(User $user): array
    {
        return [
            'user_id' => $user->id,
            'mfa_enabled' => (bool) ($user->two_factor_secret ?? false),
            'mfa_confirmed_at' => $user->two_factor_confirmed_at ?? null,
        ];
    }

    public function enforceMfa(User $user): void
    {
        DB::transaction(function () use ($user): void {
            // Set a platform-level flag requiring MFA on next login
            $user->update(['mfa_required' => true]);

            $this->audit->log(
                event: AuditEventType::STAFF_MFA_ENFORCED->value,
                action: 'enforce',
                subject: $user,
                description: "MFA enforcement set for staff user {$user->id}"
            );
        });
    }

    public function resetMfa(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->update([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ]);

            $this->audit->log(
                event: AuditEventType::STAFF_MFA_RESET->value,
                action: 'reset',
                subject: $user,
                description: "MFA reset for staff user {$user->id}"
            );
        });
    }

    public function configureSso(array $data): void
    {
        DB::transaction(function () use ($data): void {
            // Persist SSO config to platform settings
            $settings = [
                'sso_enabled' => $data['enabled'] ?? false,
                'sso_provider' => $data['provider'] ?? null,
                'sso_client_id' => $data['client_id'] ?? null,
                'sso_client_secret' => $data['client_secret'] ?? null,
                'sso_metadata_url' => $data['metadata_url'] ?? null,
            ];

            foreach ($settings as $key => $value) {
                DB::connection('central')->table('platform_settings')
                    ->updateOrInsert(['key' => $key], ['value' => $value]);
            }

            $this->audit->log(
                event: AuditEventType::STAFF_SSO_CONFIGURED->value,
                action: 'configure',
                subject: null,
                description: 'Staff SSO configured (provider: '.($data['provider'] ?? 'none').')'
            );
        });
    }

    public function addIpAllowlist(array $data): StaffIpAllowlist
    {
        return DB::transaction(function () use ($data): StaffIpAllowlist {
            $entry = StaffIpAllowlist::create($data);

            $this->audit->log(
                event: AuditEventType::IP_ALLOWLIST_ENTRY_ADDED->value,
                action: 'create',
                subject: $entry,
                description: "IP allowlist entry added: {$entry->ip_cidr} ({$entry->label})"
            );

            return $entry;
        });
    }

    public function removeIpAllowlist(StaffIpAllowlist $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $cidr = $entry->ip_cidr;
            $entry->delete();

            $this->audit->log(
                event: AuditEventType::IP_ALLOWLIST_ENTRY_REMOVED->value,
                action: 'delete',
                subject: null,
                description: "IP allowlist entry removed: {$cidr}"
            );
        });
    }

    /** @return Collection<int, StaffIpAllowlist> */
    public function getActiveIpAllowlist(): Collection
    {
        return StaffIpAllowlist::where('is_active', true)->orderByDesc('created_at')->get();
    }

    /**
     * Check if a given IP address is within any active CIDR rule.
     */
    public function isIpAllowed(string $ip): bool
    {
        $entries = $this->getActiveIpAllowlist();

        if ($entries->isEmpty()) {
            return true; // No rules = open access
        }

        foreach ($entries as $entry) {
            if ($this->ipMatchesCidr($ip, $entry->ip_cidr)) {
                return true;
            }
        }

        return false;
    }

    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $prefix] = explode('/', $cidr);
        $prefixLen = (int) $prefix;

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = $prefixLen === 0 ? 0 : (~0 << (32 - $prefixLen));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
