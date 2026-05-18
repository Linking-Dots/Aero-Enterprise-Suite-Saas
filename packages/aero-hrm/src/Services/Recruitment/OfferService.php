<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Recruitment;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\RecruitmentAuditEvents;
use Aero\HRM\Models\JobApplication;
use Aero\HRM\Models\JobOffer;
use Illuminate\Support\Facades\DB;

class OfferService
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
    ) {}

    public function templates(): array
    {
        return [];
    }

    public function extend(array $data, mixed $actor): JobOffer
    {
        return DB::transaction(function () use ($data, $actor): JobOffer {
            $application = JobApplication::findOrFail($data['application_id']);

            $offer = JobOffer::create(array_merge($data, [
                'sent_by' => $actor->id,
                'sent_at' => now(),
                'status' => 'sent',
            ]));

            $application->fill(['status' => 'offered']);
            $application->save();

            $this->audit->log(
                event: RecruitmentAuditEvents::OFFER_SENT,
                action: 'sent',
                subject: $offer,
                description: "Offer sent to application #{$application->id}.",
            );

            return $offer;
        });
    }
}
