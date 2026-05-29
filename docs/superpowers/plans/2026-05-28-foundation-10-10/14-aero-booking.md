# aero-booking — Plan to 10/10

> **For agentic workers:** Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Current state:** **STUB** — 2 files (`config/module.php` + ServiceProvider), 0 migrations, 0 controllers, 0 models, 0 routes, 0 tests.
**Current score:** 2/10
**Target score:** 10/10 OR **REMOVED**
**Estimated effort:** 0.5d (remove) OR 8-12d (implement)

**Goal:** Booking & Scheduling declares the intent (calendar, resource scheduling, availability, payments, reminders, cancellation policies) but ships nothing.

**Architecture (if implement):** Resource → Slot → Booking model. Availability engine resolves open slots from resource calendar + existing bookings + business hours. Payment integration via Cashier or platform billing gateway. Reminder via aero-notifications.

**Tech Stack:** Laravel 12, Inertia v2, TenantModel, Cashier/Stripe (optional), aero-notifications.

**Prerequisite:** Phase 0 wiring + aero-notifications Task 1 (declared submodules).

---

## Decision Branch

### Branch A — Remove

If booking isn't a Q3 priority.

- [ ] **Step 1: Delete `packages/aero-booking/`**
- [ ] **Step 2: Remove from monorepo composer paths**
- [ ] **Step 3: grep for imports + remove HRMAC actions**
- [ ] **Step 4: Commit**

```bash
git commit -am "chore: remove aero-booking stub (deferred)"
```

### Branch B — Implement

## File Structure (Branch B)

| File | Responsibility |
|---|---|
| `config/module.php` | Declare 5 components: calendar, resources, bookings, availability, policies |
| `database/migrations/*_create_resources_table.php` | Bookable resource (room, person, equipment) |
| `database/migrations/*_create_resource_calendars_table.php` | Business hours per resource |
| `database/migrations/*_create_bookings_table.php` | Booking record + status + payment ref |
| `database/migrations/*_create_booking_policies_table.php` | Cancellation/reschedule rules |
| `src/Models/Resource.php`, `ResourceCalendar.php`, `Booking.php`, `BookingPolicy.php` | TenantModels |
| `src/Services/AvailabilityEngine.php` | Resolve open slots |
| `src/Services/BookingService.php` | Create/cancel/reschedule |
| `src/Services/BookingPaymentService.php` | Integrate with Cashier or aero-platform billing |
| `src/Notifications/BookingConfirmationNotification.php`, `ReminderNotification.php`, `CancellationNotification.php` |  |
| `src/Jobs/SendBookingReminderJob.php` | Scheduled |
| `src/Http/Controllers/*Controller.php` × 5 |  |
| `src/Policies/*Policy.php` × 4 |  |
| `routes/web.php` | Resource routes + HRMAC |
| `tests/Feature/Booking/*Test.php` | Per-action coverage |

## Tasks (Branch B)

1. Migrations + models
2. AvailabilityEngine (the heart of the system — heavy test coverage on slot calculation edge cases)
3. BookingService (create/cancel/reschedule with policy enforcement)
4. Payment integration
5. Notification + reminder job
6. Controllers + routes + Form Requests
7. Policies + defense-in-depth
8. Audit trail
9. Tests (per service + per controller + concurrency tests for double-booking)
10. Inertia pages (coordinate with aero-ui plan)
11. Final verification

**Critical:** Tests must cover concurrency — two users booking the same slot at the same instant. DB unique constraint + advisory lock pattern.

---

## Recommendation

**Branch A (remove)** unless booking is a near-term feature. The 10-12 day implementation cost is significant. If your customers need scheduling, evaluate integration with an external service (Calendly, Cal.com) first.
