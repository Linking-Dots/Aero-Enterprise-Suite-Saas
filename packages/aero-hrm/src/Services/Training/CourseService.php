<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Training;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Audit\TrainingAuditEvents;
use Aero\HRM\Models\TrainingCourse;
use Aero\HRM\Models\TrainingCourseMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseService
{
    public function __construct(
        protected AuditServiceInterface $audit,
    ) {}

    public function create(array $data, mixed $actor): TrainingCourse
    {
        return DB::transaction(function () use ($data, $actor): TrainingCourse {
            $data['slug'] = Str::slug($data['title']);
            $data['created_by'] = $actor->id;

            $materials = $data['materials'] ?? [];
            unset($data['materials']);

            /** @var TrainingCourse $course */
            $course = TrainingCourse::create($data);

            if (! empty($materials)) {
                foreach ($materials as $material) {
                    TrainingCourseMaterial::create([
                        'course_id' => $course->id,
                        'kind' => $material['kind'] ?? 'link',
                        'label' => $material['label'],
                        'url' => $material['url'] ?? null,
                        'file_path' => $material['file_path'] ?? null,
                        'display_order' => $material['display_order'] ?? 0,
                    ]);
                }
            }

            $this->audit->log(
                event: TrainingAuditEvents::COURSE_CREATED,
                action: 'created',
                subject: $course,
                description: "Training course '{$course->title}' created.",
            );

            return $course->load(['category', 'materials']);
        });
    }

    public function update(TrainingCourse $course, array $data): void
    {
        DB::transaction(function () use ($course, $data): void {
            $materials = $data['materials'] ?? null;
            unset($data['materials']);

            // Do not update slug on edit
            unset($data['slug']);

            $course->update($data);

            if ($materials !== null) {
                $course->materials()->delete();
                foreach ($materials as $material) {
                    TrainingCourseMaterial::create([
                        'course_id' => $course->id,
                        'kind' => $material['kind'] ?? 'link',
                        'label' => $material['label'],
                        'url' => $material['url'] ?? null,
                        'file_path' => $material['file_path'] ?? null,
                        'display_order' => $material['display_order'] ?? 0,
                    ]);
                }
            }

            $this->audit->log(
                event: TrainingAuditEvents::COURSE_UPDATED,
                action: 'updated',
                subject: $course,
                description: "Training course '{$course->title}' updated.",
            );
        });
    }
}
