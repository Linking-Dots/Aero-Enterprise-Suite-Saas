<?php

namespace Aero\HRM\Services\Benefits;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Models\HrmBenefit;
use Illuminate\Support\Facades\DB;

final class BenefitCatalogService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function create(array $data): HrmBenefit
    {
        $benefit = HrmBenefit::create($data);
        $this->audit->log(event: 'BENEFIT_CREATED', action: 'create', subject: $benefit, description: "Created benefit: {$benefit->name}");

        return $benefit;
    }

    public function update(HrmBenefit $benefit, array $data): HrmBenefit
    {
        $benefit->update($data);
        $this->audit->log(event: 'BENEFIT_UPDATED', action: 'update', subject: $benefit, description: "Updated benefit: {$benefit->name}");

        return $benefit;
    }

    public function delete(HrmBenefit $benefit): void
    {
        abort_if($benefit->enrollments()->exists(), 422, 'Has active enrollments — deactivate instead.');

        DB::transaction(function () use ($benefit) {
            $name = $benefit->name;
            $benefit->delete();
            $this->audit->log(event: 'BENEFIT_DELETED', action: 'delete', subject: $benefit, description: "Deleted benefit: {$name}");
        });
    }
}
