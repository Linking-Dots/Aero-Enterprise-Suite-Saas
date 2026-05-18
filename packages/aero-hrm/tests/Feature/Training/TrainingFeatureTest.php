<?php

declare(strict_types=1);

namespace Aero\HRM\Tests\Feature\Training;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess;
use Aero\HRM\AeroHrmServiceProvider;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\TrainingCategory;
use Aero\HRM\Models\TrainingCourse;
use Aero\HRM\Models\TrainingEnrollment;
use Aero\HRM\Models\TrainingSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class TrainingFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Inertia\ServiceProvider::class,
            AeroCoreServiceProvider::class,
            HRMACServiceProvider::class,
            AeroHrmServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('view.paths', [
            realpath(__DIR__ . '/../../fixtures/views'),
        ]);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/login', fn () => response('login'))->name('login');
    }

    private function grantAllPermissions(): void
    {
        Gate::before(fn () => true);
    }

    /**
     * Act as a user with all middleware disabled and X-Inertia headers set.
     * Use for routes without route-model binding (index, store).
     */
    private function asUser(?User $user = null)
    {
        $user ??= User::factory()->create(['email_verified_at' => now()]);

        return $this->actingAs($user)
            ->withoutMiddleware()
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1']);
    }

    /**
     * Act as a user keeping SubstituteBindings so route model binding resolves.
     * Disables only HRMAC middleware.
     * Use for routes with a {model} parameter: show, update, destroy, cancel.
     */
    private function asUserWithBinding(?User $user = null)
    {
        $user ??= User::factory()->create(['email_verified_at' => now()]);

        return $this->actingAs($user)
            ->withoutMiddleware([CheckRoleModuleAccess::class])
            ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1']);
    }

    // -------------------------------------------------------------------------
    // 1. Admin can create a course
    // -------------------------------------------------------------------------

    public function test_admin_can_create_course(): void
    {
        $this->grantAllPermissions();

        $category = TrainingCategory::factory()->create();

        $this->asUser()
            ->post(route('hrm.training.courses.store'), [
                'category_id'      => $category->id,
                'title'            => 'Leadership Fundamentals',
                'delivery_mode'    => 'in_person',
                'duration_minutes' => 90,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('training_courses', [
            'category_id' => $category->id,
            'title'       => 'Leadership Fundamentals',
        ]);
    }

    // -------------------------------------------------------------------------
    // 2. Admin can update a course
    // -------------------------------------------------------------------------

    public function test_admin_can_update_course(): void
    {
        $this->grantAllPermissions();

        $course = TrainingCourse::factory()->create(['title' => 'Old Title']);

        $this->asUserWithBinding()
            ->patch(route('hrm.training.courses.update', $course), [
                'title' => 'Updated Title',
            ])
            ->assertRedirect();

        $this->assertEquals('Updated Title', $course->fresh()->title);
    }

    // -------------------------------------------------------------------------
    // 3. Course with an upcoming session cannot be deleted
    // -------------------------------------------------------------------------

    public function test_course_with_upcoming_session_cannot_be_deleted(): void
    {
        $this->grantAllPermissions();

        $course = TrainingCourse::factory()->create();

        TrainingSession::factory()->create([
            'course_id'  => $course->id,
            'starts_at'  => now()->addDay(),
            'status'     => TrainingSession::STATUS_SCHEDULED,
        ]);

        $this->asUserWithBinding()
            ->delete(route('hrm.training.courses.destroy', $course))
            ->assertStatus(422);

        $this->assertDatabaseHas('training_courses', ['id' => $course->id]);
    }

    // -------------------------------------------------------------------------
    // 4. Admin can enroll employees
    // -------------------------------------------------------------------------

    public function test_admin_can_enroll_employees(): void
    {
        $this->grantAllPermissions();

        $session   = TrainingSession::factory()->create(['capacity' => 5]);
        $employees = Employee::factory()->count(3)->create();

        $this->asUser()
            ->post('/hrm/training/enrollments', [
                'session_id'   => $session->id,
                'employee_ids' => $employees->pluck('id')->toArray(),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('training_enrollments', 3);

        foreach ($employees as $employee) {
            $this->assertDatabaseHas('training_enrollments', [
                'session_id'  => $session->id,
                'employee_id' => $employee->id,
                'status'      => TrainingEnrollment::STATUS_ENROLLED,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // 5. Over-capacity enrollments go to waitlist
    // -------------------------------------------------------------------------

    public function test_overcapacity_enrollments_go_to_waitlist(): void
    {
        $this->grantAllPermissions();

        $session   = TrainingSession::factory()->create(['capacity' => 2]);
        $employees = Employee::factory()->count(3)->create();

        $this->asUser()
            ->post('/hrm/training/enrollments', [
                'session_id'   => $session->id,
                'employee_ids' => $employees->pluck('id')->toArray(),
            ])
            ->assertRedirect();

        $enrolled   = TrainingEnrollment::where('session_id', $session->id)
            ->where('status', TrainingEnrollment::STATUS_ENROLLED)
            ->count();

        $waitlisted = TrainingEnrollment::where('session_id', $session->id)
            ->where('status', TrainingEnrollment::STATUS_WAITLISTED)
            ->count();

        $this->assertEquals(2, $enrolled,   'Expected exactly 2 enrolled.');
        $this->assertEquals(1, $waitlisted, 'Expected exactly 1 waitlisted.');
    }

    // -------------------------------------------------------------------------
    // 6. Mark attendance updates enrollment statuses
    // -------------------------------------------------------------------------

    public function test_mark_attendance_updates_enrollments(): void
    {
        $this->grantAllPermissions();

        $session = TrainingSession::factory()->create();

        $enrollment1 = TrainingEnrollment::factory()->create([
            'session_id' => $session->id,
            'status'     => TrainingEnrollment::STATUS_ENROLLED,
        ]);
        $enrollment2 = TrainingEnrollment::factory()->create([
            'session_id' => $session->id,
            'status'     => TrainingEnrollment::STATUS_ENROLLED,
        ]);

        $this->asUserWithBinding()
            ->post(route('hrm.training.enrollments.attendance', $session), [
                'attendance' => [
                    $enrollment1->id => TrainingEnrollment::STATUS_ATTENDED,
                    $enrollment2->id => TrainingEnrollment::STATUS_NO_SHOW,
                ],
            ])
            ->assertRedirect();

        $this->assertEquals(
            TrainingEnrollment::STATUS_ATTENDED,
            $enrollment1->fresh()->status,
        );
        $this->assertNotNull(
            $enrollment1->fresh()->attended_at,
            'attended_at must be set when status is attended.',
        );

        $this->assertEquals(
            TrainingEnrollment::STATUS_NO_SHOW,
            $enrollment2->fresh()->status,
        );
    }
}
