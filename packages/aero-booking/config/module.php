<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Booking Module — Booking and Scheduling System
    |--------------------------------------------------------------------------
    |
    | Booking and scheduling with calendar, resource scheduling, availability,
    * payments, reminders, and cancellation policies.
    *
    | Tenancy concern:
    *   - All bookings are tenant-scoped
    *   - Resources are tenant-scoped
    *   - Availability is tenant-scoped
    *   - No cross-tenant booking data access
    */

    'code'         => 'booking',
    'scope'        => 'tenant',
    'name'         => 'Booking & Scheduling',
    'description'  => 'Booking and scheduling: booking calendar, resource scheduling, availability, payments, reminders, and cancellation policies.',
    'icon'         => 'CalendarDaysIcon',
    'route_prefix' => '/booking',
    'category'     => 'infrastructure',
    'priority'     => 10,
    'is_core'      => false,
    'is_active'    => true,
    'enabled'      => env('BOOKING_MODULE_ENABLED', true),
    'version'      => '1.0.0',
    'min_plan'     => 'basic',
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',

    'features' => [
        'booking_calendar'        => true,
        'resource_scheduling'    => true,
        'availability'           => true,
        'payments'               => true,
        'reminders'              => true,
        'cancellation_policies'  => true,
        'booking_analytics'      => true,
        'multi_resource'         => true,
        'recurring_bookings'     => true,
        'waitlist'               => true,
        'booking_templates'      => true,
        'settings'               => true,
    ],

    'submodules' => [
        // Calendar, bookings, resources, analytics
    ],
];
