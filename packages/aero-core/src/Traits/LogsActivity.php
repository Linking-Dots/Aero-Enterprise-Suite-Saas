<?php

namespace Aero\Core\Traits;

use Aero\Core\Services\ActivityService;

trait LogsActivity
{
    protected ActivityService $activityService;

    /**
     * Log an activity event.
     */
    protected function logActivity(string $action, string $description, array $metadata = []): void
    {
        if (!isset($this->activityService)) {
            $this->activityService = app(ActivityService::class);
        }

        $this->activityService->log([
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a user creation event.
     */
    protected function logUserCreated($user, array $metadata = []): void
    {
        $this->logActivity('created', "User created: {$user->name}", array_merge($metadata, [
            'user_id' => $user->id,
            'email' => $user->email,
        ]));
    }

    /**
     * Log a user update event.
     */
    protected function logUserUpdated($user, array $metadata = []): void
    {
        $this->logActivity('updated', "User updated: {$user->name}", array_merge($metadata, [
            'user_id' => $user->id,
        ]));
    }

    /**
     * Log a user deletion event.
     */
    protected function logUserDeleted($user, array $metadata = []): void
    {
        $this->logActivity('deleted', "User deleted: {$user->name}", array_merge($metadata, [
            'user_id' => $user->id,
            'email' => $user->email,
        ]));
    }

    /**
     * Log a role creation event.
     */
    protected function logRoleCreated($role, array $metadata = []): void
    {
        $this->logActivity('created', "Role created: {$role->name}", array_merge($metadata, [
            'role_id' => $role->id,
        ]));
    }

    /**
     * Log a role update event.
     */
    protected function logRoleUpdated($role, array $metadata = []): void
    {
        $this->logActivity('updated', "Role updated: {$role->name}", array_merge($metadata, [
            'role_id' => $role->id,
        ]));
    }

    /**
     * Log a role deletion event.
     */
    protected function logRoleDeleted($role, array $metadata = []): void
    {
        $this->logActivity('deleted', "Role deleted: {$role->name}", array_merge($metadata, [
            'role_id' => $role->id,
        ]));
    }

    /**
     * Log a tag creation event.
     */
    protected function logTagCreated($tag, array $metadata = []): void
    {
        $this->logActivity('created', "Tag created: {$tag->name}", array_merge($metadata, [
            'tag_id' => $tag->id,
        ]));
    }

    /**
     * Log a tag update event.
     */
    protected function logTagUpdated($tag, array $metadata = []): void
    {
        $this->logActivity('updated', "Tag updated: {$tag->name}", array_merge($metadata, [
            'tag_id' => $tag->id,
        ]));
    }

    /**
     * Log a tag deletion event.
     */
    protected function logTagDeleted($tag, array $metadata = []): void
    {
        $this->logActivity('deleted', "Tag deleted: {$tag->name}", array_merge($metadata, [
            'tag_id' => $tag->id,
        ]));
    }
}
