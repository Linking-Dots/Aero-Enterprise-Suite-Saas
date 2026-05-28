<?php

namespace Aero\Notifications\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\SystemSettingService;
use Inertia\Inertia;
use Inertia\Response;

class DeliverabilityController extends Controller
{
    public function __construct(private SystemSettingService $settings) {}

    public function index(): Response
    {
        $fromEmail = $this->settings->get('mail_from_email', '');
        $domain = $fromEmail ? substr(strrchr($fromEmail, '@'), 1) : parse_url(config('app.url'), PHP_URL_HOST);

        $checks = $this->runDnsChecks($domain ?? 'localhost');

        return Inertia::render('Core/Email/Deliverability', [
            'domain' => $domain,
            'checks' => $checks,
            'score' => $this->score($checks),
        ]);
    }

    private function runDnsChecks(string $domain): array
    {
        $spfRecords = @dns_get_record($domain, DNS_TXT) ?: [];
        $spf = collect($spfRecords)->first(fn ($r) => str_contains($r['txt'] ?? '', 'v=spf1'));
        $dmarc = @dns_get_record("_dmarc.{$domain}", DNS_TXT)[0] ?? null;

        $dkimFound = false;
        foreach (['default._domainkey', 'mail._domainkey', 's1._domainkey'] as $sel) {
            if (! empty(@dns_get_record("{$sel}.{$domain}", DNS_TXT))) {
                $dkimFound = true;
                break;
            }
        }

        $mx = @dns_get_record($domain, DNS_MX) ?: [];

        return [
            'spf' => ['label' => 'SPF',   'status' => $spf ? 'pass' : 'fail', 'value' => $spf['txt'] ?? null,  'guide' => 'Add TXT record: v=spf1 include:yourprovider.com ~all'],
            'dmarc' => ['label' => 'DMARC', 'status' => $dmarc ? 'pass' : 'fail', 'value' => $dmarc['txt'] ?? null, 'guide' => 'Add TXT at _dmarc.'.$domain.': v=DMARC1; p=quarantine;'],
            'dkim' => ['label' => 'DKIM',  'status' => $dkimFound ? 'pass' : 'warn', 'value' => null,                  'guide' => 'Configure DKIM through your mail provider'],
            'mx' => ['label' => 'MX',    'status' => ! empty($mx) ? 'pass' : 'warn', 'value' => collect($mx)->pluck('target')->join(', '), 'guide' => 'Add MX records for your domain'],
        ];
    }

    private function score(array $checks): int
    {
        $weights = ['spf' => 30, 'dmarc' => 30, 'dkim' => 30, 'mx' => 10];
        $score = 0;
        foreach ($checks as $k => $c) {
            if ($c['status'] === 'pass') {
                $score += $weights[$k] ?? 10;
            }
            if ($c['status'] === 'warn') {
                $score += ($weights[$k] ?? 10) / 2;
            }
        }

        return (int) $score;
    }
}
