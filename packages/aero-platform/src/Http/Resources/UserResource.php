<?php

namespace Aero\Platform\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'employee_id' => $this->employee_id,
            'profile_image_url' => $this->profile_image_url,
            'active' => $this->deleted_at === null,

            // Basic Information
            'user_name' => $this->user_name,
            'birthday' => $this->birthday,
            'gender' => $this->gender,
            'address' => $this->address,
            'about' => $this->about,

            // Work Information
            'date_of_joining' => $this->date_of_joining,
            'report_to' => $this->report_to,
            'reports_to' => $this->when($this->reportsTo, function () {
                return [
                    'id' => $this->reportsTo->id,
                    'name' => $this->reportsTo->name,
                    'profile_image_url' => $this->reportsTo->profile_image_url,
                ];
            }),
            // Note: Using role check instead of Spatie permission check
            // since we use role_module_access instead of permissions table
            'salary_amount' => $this->when(
                $this->canViewSalary($request),
                $this->salary_amount
            ),

            // Relationships
            'department' => [
                'id' => $this->department_id,
                'name' => $this->department?->name,
            ],
            'designation' => [
                'id' => $this->designation_id,
                'title' => $this->designation?->title,
            ],
            'attendance_type' => [
                'id' => $this->attendance_type_id,
                'name' => $this->attendanceType?->name,
                'slug' => $this->attendanceType?->slug,
            ],
            'roles' => $this->roles->pluck('name')->toArray(),

            // Device information
            'single_device_login' => $this->single_device_login_enabled,
            'single_device_login_enabled' => $this->single_device_login_enabled,
            'active_device' => $this->when($this->currentDevice, function () {
                return [
                    'id' => $this->currentDevice->id,
                    'device_name' => $this->currentDevice->device_name,
                    'device_type' => $this->currentDevice->device_type,
                    'last_seen_at' => $this->currentDevice->last_used_at,
                ];
            }),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->when($this->trashed(), $this->deleted_at),

            // Authorization flags (only when user is authenticated)
            // Note: Using try-catch to gracefully handle cases where
            // Spatie permission checks might fail (no permissions table)
            'can' => $this->when($request->user(), function () use ($request) {
                return $this->getAuthorizationFlags($request);
            }),
        ];
    }

    /**
     * Check if the current user can view salary information.
     * Uses role-based check instead of Spatie permission check since
     * we use role_module_access instead of permissions table.
     */
    protected function canViewSalary(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        // Super Administrators and platform admins can view salary
        if ($user->hasRole(['Super Administrator', 'super-admin', 'Platform Admin'])) {
            return true;
        }

        // If roles have is_protected flag, grant access
        foreach ($user->roles as $role) {
            if ($role->is_protected ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get authorization flags for the resource.
     * Gracefully handles cases where policy checks might fail.
     */
    protected function getAuthorizationFlags(Request $request): array
    {
        $user = $request->user();

        // For platform admin users, grant all permissions
        if ($user->hasRole(['Super Administrator', 'super-admin', 'Platform Admin'])) {
            return [
                'view' => true,
                'update' => true,
                'delete' => true,
                'update_roles' => true,
                'toggle_status' => true,
                'manage_devices' => true,
            ];
        }

        // For protected roles, grant all permissions
        foreach ($user->roles as $role) {
            if ($role->is_protected ?? false) {
                return [
                    'view' => true,
                    'update' => true,
                    'delete' => true,
                    'update_roles' => true,
                    'toggle_status' => true,
                    'manage_devices' => true,
                ];
            }
        }

        // Try policy-based checks with fallback to false
        try {
            return [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'update_roles' => $user->can('updateRoles', $this->resource),
                'toggle_status' => $user->can('toggleStatus', $this->resource),
                'manage_devices' => $user->can('manageDevices', $this->resource),
            ];
        } catch (\Throwable) {
            // If permission checks fail (e.g., no permissions table), return false for all
            return [
                'view' => false,
                'update' => false,
                'delete' => false,
                'update_roles' => false,
                'toggle_status' => false,
                'manage_devices' => false,
            ];
        }
    }
}
