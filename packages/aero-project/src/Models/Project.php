<?php

namespace Aero\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Project Model
 *
 * Core entity for the project management module.
 *
 * ARCHITECTURAL PRINCIPLES:
 * - Uses user_id references (NOT employee_id) for team assignments
 * - Department relationships are contextual (department_context JSON)
 * - Project type defines domain-specific behavior via config
 * - HRMAC controls all access through permission paths
 *
 * @property string $project_type The project type code (references config/project_types.php)
 * @property array|null $department_context JSON of department involvements
 * @property string|null $methodology Project methodology (agile, scrum, waterfall, etc.)
 * @property array|null $type_specific_data Domain-specific fields from project type
 * @property array|null $enabled_features Array of enabled feature codes
 */
class Project extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'project_name',
        'code',
        'description',
        'client_id',
        'department_id',
        'start_date',
        'end_date',
        'project_leader_id',
        'team_leader_id',
        'budget',
        'rate',
        'rate_type',
        'status',
        'priority',
        'progress',
        'color',
        'files',
        'notes',
        // Project type system
        'project_type',
        'methodology',
        'sprint_duration',
        'workflow',
        'department_context',
        'type_specific_data',
        'enabled_features',
        // domain-specific (infrastructure)
        'type',
        'category',
        'location',
        'start_chainage',
        'end_chainage',
        'total_length',
        'boundary_lat_min',
        'boundary_lat_max',
        'boundary_lng_min',
        'boundary_lng_max',
        'geofence_settings',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'files' => 'array',
        'budget' => 'decimal:2',
        'progress' => 'integer',
        'sprint_duration' => 'integer',
        'department_context' => 'array',
        'type_specific_data' => 'array',
        'enabled_features' => 'array',
        'start_chainage' => 'decimal:3',
        'end_chainage' => 'decimal:3',
        'total_length' => 'decimal:3',
        'boundary_lat_min' => 'decimal:7',
        'boundary_lat_max' => 'decimal:7',
        'boundary_lng_min' => 'decimal:7',
        'boundary_lng_max' => 'decimal:7',
        'geofence_settings' => 'array',
    ];

    /**
     * Default values.
     */
    protected $attributes = [
        'project_type' => 'general',
        'status' => 'not_started',
        'priority' => 'medium',
        'progress' => 0,
    ];

    // ================================================================
    // PROJECT TYPE SYSTEM
    // ================================================================

    /**
     * Get the project type configuration.
     *
     * @return array The full configuration for this project's type
     */
    public function getTypeConfig(): array
    {
        return config("project.types.{$this->project_type}", config('project.types.general', []));
    }

    /**
     * Get available fields for this project type.
     */
    public function getTypeFields(): array
    {
        return $this->getTypeConfig()['fields'] ?? [];
    }

    /**
     * Get milestones template for this project type.
     */
    public function getTypeMilestones(): array
    {
        return $this->getTypeConfig()['milestones'] ?? [];
    }

    /**
     * Get available workflows for this project type.
     */
    public function getTypeWorkflows(): array
    {
        return $this->getTypeConfig()['workflows'] ?? ['standard'];
    }

    /**
     * Check if a feature is enabled for this project.
     */
    public function hasFeature(string $featureCode): bool
    {
        // Check project-level enabled features first
        if (is_array($this->enabled_features)) {
            return in_array($featureCode, $this->enabled_features);
        }

        // Fall back to type-level features
        $typeFeatures = $this->getTypeConfig()['features'] ?? [];

        return in_array($featureCode, $typeFeatures);
    }

    /**
     * Get a type-specific field value.
     */
    public function getTypeField(string $fieldCode, mixed $default = null): mixed
    {
        return $this->type_specific_data[$fieldCode] ?? $default;
    }

    /**
     * Set a type-specific field value.
     */
    public function setTypeField(string $fieldCode, mixed $value): self
    {
        $data = $this->type_specific_data ?? [];
        $data[$fieldCode] = $value;
        $this->type_specific_data = $data;

        return $this;
    }

    // ================================================================
    // DEPARTMENT CONTEXT (Multi-Department Support)
    // ================================================================

    /**
     * Get departments involved in this project.
     *
     * NOTE: Uses DB query, not HRM model import (package isolation).
     */
    public function getInvolvedDepartments(): array
    {
        $departmentIds = collect($this->department_context ?? [])
            ->pluck('id')
            ->filter()
            ->all();

        if (empty($departmentIds)) {
            // Fall back to single department_id if context not set
            if ($this->department_id) {
                $departmentIds = [$this->department_id];
            } else {
                return [];
            }
        }

        return DB::table('departments')
            ->whereIn('id', $departmentIds)
            ->get(['id', 'name', 'code'])
            ->toArray();
    }

    /**
     * Get the primary (owner) department.
     */
    public function getPrimaryDepartment(): ?array
    {
        $context = $this->department_context ?? [];

        foreach ($context as $dept) {
            if (($dept['role'] ?? '') === 'owner' || ($dept['is_primary'] ?? false)) {
                return DB::table('departments')
                    ->where('id', $dept['id'])
                    ->first(['id', 'name', 'code']);
            }
        }

        // Fall back to department_id
        if ($this->department_id) {
            return (array) DB::table('departments')
                ->where('id', $this->department_id)
                ->first(['id', 'name', 'code']);
        }

        return null;
    }

    // ================================================================
    // INTERNAL RELATIONSHIPS (Tenant-Scoped)
    // ================================================================

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function issues()
    {
        return $this->hasMany(ProjectIssue::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(ProjectTimeEntry::class);
    }

    public function budgets()
    {
        return $this->hasMany(ProjectBudget::class);
    }

    public function budgetExpenses()
    {
        return $this->hasMany(ProjectBudgetExpense::class);
    }

    public function projectResources()
    {
        return $this->hasMany(ProjectResource::class);
    }

    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function departmentInvolvements()
    {
        return $this->hasMany(ProjectDepartmentInvolvement::class);
    }

    public function workflowSteps()
    {
        return $this->hasMany(ProjectWorkflowStep::class);
    }

    public function typeMetadata()
    {
        return $this->hasMany(ProjectTypeMetadata::class);
    }

    /**
     * Get sprints for this project.
     */
    public function sprints(): HasMany
    {
        return $this->hasMany(ProjectSprint::class);
    }

    /**
     * Get risks for this project.
     */
    public function risks(): HasMany
    {
        return $this->hasMany(ProjectRisk::class)->where('type', 'risk');
    }

    /**
     * Get all risks and issues for this project.
     */
    public function risksAndIssues(): HasMany
    {
        return $this->hasMany(ProjectRisk::class);
    }

    /**
     * Get labels for this project.
     */
    public function labels(): HasMany
    {
        return $this->hasMany(ProjectLabel::class);
    }

    /**
     * Get comments for this project.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(ProjectComment::class, 'commentable');
    }

    /**
     * Get attachments for this project.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(ProjectAttachment::class, 'attachable');
    }

    /**
     * Get watchers for this project.
     */
    public function watchers(): MorphMany
    {
        return $this->morphMany(ProjectWatcher::class, 'watchable');
    }

    /**
     * Get activity log for this project.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ProjectActivityLog::class);
    }

    /**
     * Get the active sprint.
     */
    public function activeSprint()
    {
        return $this->hasOne(ProjectSprint::class)->where('status', 'active');
    }

    // ================================================================
    // HELPER METHODS
    // ================================================================

    public function calculateProgress(): int
    {
        $tasks = $this->tasks;

        if ($tasks->isEmpty()) {
            return 0;
        }

        $completedTasks = $tasks->where('status', 'completed')->count();
        $totalTasks = $tasks->count();

        return (int) (($completedTasks / $totalTasks) * 100);
    }

    public function getStatusTextAttribute(): string
    {
        $statusMap = [
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'on_hold' => 'On Hold',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        return $statusMap[$this->status] ?? $this->status;
    }
}
