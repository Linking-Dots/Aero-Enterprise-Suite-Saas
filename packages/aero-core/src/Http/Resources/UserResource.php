<?php

namespace Aero\Core\Http\Resources;

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
            'salary_amount' => $this->when(
                $request->user()?->can('view-salary', $this->resource),
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
            'can' => $this->when($request->user(), function () use ($request) {
                return [
                    'view' => $request->user()->can('view', $this->resource),
                    'update' => $request->user()->can('update', $this->resource),
                    'delete' => $request->user()->can('delete', $this->resource),
                    'update_roles' => $request->user()->can('updateRoles', $this->resource),
                    'toggle_status' => $request->user()->can('toggleStatus', $this->resource),
                    'manage_devices' => $request->user()->can('manageDevices', $this->resource),
                ];
            }),
        ];
    }
}
