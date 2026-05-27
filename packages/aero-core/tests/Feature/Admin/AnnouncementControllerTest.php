<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Feature\Admin;

use Aero\Core\Models\Announcement;
use Aero\Core\Tests\PackageTestCase;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Feature tests for AnnouncementController (CA-1).
 *
 * Run:
 *   php c:/laragon/www/aeos365/vendor/bin/phpunit \
 *     --configuration packages/aero-core/phpunit.xml \
 *     packages/aero-core/tests/Feature/Admin/AnnouncementControllerTest.php
 */
class AnnouncementControllerTest extends PackageTestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeAnnouncement(array $attrs = []): Announcement
    {
        return Announcement::create(array_merge([
            'title'    => 'Test Announcement',
            'body'     => 'Test body content',
            'type'     => 'info',
            'status'   => 'draft',
            'audience' => 'all',
        ], $attrs));
    }

    // =========================================================================
    // Index
    // =========================================================================

    public function test_index_renders_announcement_list(): void
    {
        $admin = $this->makeSuperAdmin();
        $this->makeAnnouncement();

        $this->actingAs($admin)
            ->get(route('core.announcements.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Core/Announcements/Index', false)
                ->has('announcements')
                ->has('filters')
            );
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('core.announcements.index'))
            ->assertRedirect(route('login'));
    }

    // =========================================================================
    // Store
    // =========================================================================

    public function test_store_creates_announcement(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->post(route('core.announcements.store'), [
                'title'    => 'Hello World',
                'body'     => 'Body text content',
                'type'     => 'info',
                'status'   => 'draft',
                'audience' => 'all',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('announcements', ['title' => 'Hello World']);
    }

    public function test_store_sets_created_by_to_acting_user(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->post(route('core.announcements.store'), [
                'title'    => 'Authored',
                'body'     => 'Body',
                'type'     => 'info',
                'status'   => 'draft',
                'audience' => 'all',
            ]);

        $this->assertDatabaseHas('announcements', [
            'title'      => 'Authored',
            'created_by' => $admin->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->post(route('core.announcements.store'), [])
            ->assertSessionHasErrors(['title', 'body', 'type', 'status', 'audience']);
    }

    public function test_store_rejects_invalid_type(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->post(route('core.announcements.store'), [
                'title'    => 'Test',
                'body'     => 'Body',
                'type'     => 'not-a-valid-type',
                'status'   => 'draft',
                'audience' => 'all',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_store_rejects_invalid_status(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->post(route('core.announcements.store'), [
                'title'    => 'Test',
                'body'     => 'Body',
                'type'     => 'info',
                'status'   => 'not-a-valid-status',
                'audience' => 'all',
            ])
            ->assertSessionHasErrors('status');
    }

    // =========================================================================
    // Publish
    // =========================================================================

    public function test_publish_changes_status_to_published(): void
    {
        $admin = $this->makeSuperAdmin();
        $ann   = $this->makeAnnouncement(['status' => 'draft']);

        $this->actingAs($admin)
            ->post(route('core.announcements.publish', $ann));

        $this->assertDatabaseHas('announcements', [
            'id'     => $ann->id,
            'status' => 'published',
        ]);
    }

    public function test_publish_sets_published_at_timestamp(): void
    {
        $admin = $this->makeSuperAdmin();
        $ann   = $this->makeAnnouncement(['status' => 'draft']);

        $this->actingAs($admin)
            ->post(route('core.announcements.publish', $ann));

        $this->assertNotNull($ann->fresh()->published_at);
    }

    // =========================================================================
    // Archive
    // =========================================================================

    public function test_archive_changes_status_to_archived(): void
    {
        $admin = $this->makeSuperAdmin();
        $ann   = $this->makeAnnouncement(['status' => 'published', 'published_at' => now()]);

        $this->actingAs($admin)
            ->post(route('core.announcements.archive', $ann));

        $this->assertDatabaseHas('announcements', [
            'id'     => $ann->id,
            'status' => 'archived',
        ]);
    }

    // =========================================================================
    // Destroy
    // =========================================================================

    public function test_destroy_soft_deletes_announcement(): void
    {
        $admin = $this->makeSuperAdmin();
        $ann   = $this->makeAnnouncement();

        $this->actingAs($admin)
            ->delete(route('core.announcements.destroy', $ann));

        $this->assertSoftDeleted('announcements', ['id' => $ann->id]);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function test_archiving_an_already_archived_announcement_is_idempotent(): void
    {
        $admin = $this->makeSuperAdmin();
        $ann   = $this->makeAnnouncement(['status' => 'archived']);

        $this->actingAs($admin)
            ->post(route('core.announcements.archive', $ann))
            ->assertRedirect();

        $this->assertDatabaseHas('announcements', [
            'id'     => $ann->id,
            'status' => 'archived',
        ]);
    }

    public function test_deleted_announcement_does_not_appear_on_index(): void
    {
        $admin = $this->makeSuperAdmin();
        $ann   = $this->makeAnnouncement(['title' => 'Gone']);
        $ann->delete();

        $this->actingAs($admin)
            ->get(route('core.announcements.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('announcements.total', 0)
            );
    }
}
