<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Education Module — Learning Management System
    |--------------------------------------------------------------------------
    |
    | Manages students, faculty, courses, enrollment, grades,
    | transcripts, and attendance tracking.
    |
    */

    'code' => 'education',
    'schema_version' => '2.0',
    'scope' => 'tenant',
    'name' => 'Education & Learning Management',
    'description' => 'Full LMS + SIS: students, faculty, courses, enrollment, grading, transcripts, attendance, fees, admissions, exams, library, hostel, corporate training, SCORM/xAPI.',
    'version' => '2.0.0',
    'category' => 'industry',
    'icon' => 'AcademicCapIcon',
    'priority' => 30,
    'is_core' => false,
    'is_active' => true,
    'enabled' => env('EDUCATION_MODULE_ENABLED', true),
    'min_plan' => null,
    'minimum_plan' => null,
    'license_type' => 'standard',
    'dependencies' => ['core'],
    'release_date' => '2024-01-01',
    'route_prefix' => 'education',

    'features' => [
        'dashboard' => true,
        'students' => true,
        'faculty' => true,
        'admissions' => true,
        'courses' => true,
        'sections_classes' => true,
        'curriculum' => true,
        'enrollment' => true,
        'timetable_scheduling' => true,
        'attendance' => true,
        'assignments' => true,
        'exams' => true,
        'gradebook' => true,
        'transcripts' => true,
        'certificates' => true,
        'fees_billing' => true,
        'scholarships' => true,
        'financial_aid' => true,
        'library' => true,
        'hostel_dormitory' => true,
        'transport' => true,
        'virtual_classroom' => true,
        'content_delivery' => true,
        'scorm_xapi' => true,
        'discussion_forums' => true,
        'parent_portal' => true,
        'student_portal' => true,
        'faculty_portal' => true,
        'communication' => true,
        'corporate_training' => true,
        'alumni' => true,
        'research_management' => true,
        'reports_analytics' => true,
        'integrations' => true,
        'settings' => true,
    ],

    'submodules' => [

        // ==================== EDUCATION DASHBOARD ====================
        [
            'code' => 'education-dashboard',
            'name' => 'Education Dashboard',
            'description' => 'Overview of students, courses, enrollment metrics, and academic performance.',
            'icon' => 'ChartPieIcon',
            'route' => 'tenant.education.dashboard',
            'priority' => 1,
            'is_active' => true,
            'components' => [],
        ],

        // ==================== STUDENTS ====================
        [
            'code' => 'students',
            'name' => 'Students',
            'description' => 'Student records, transcripts, grades, and financial aid management.',
            'icon' => 'UserGroupIcon',
            'route' => 'tenant.education.students.index',
            'priority' => 2,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'student-list',
                    'name' => 'Student List',
                    'description' => 'View and manage all student records.',
                    'route_name' => 'tenant.education.students.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'student-transcript',
                    'name' => 'Student Transcript',
                    'description' => 'View student academic transcripts.',
                    'route_name' => 'tenant.education.students.transcript',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'student-grades',
                    'name' => 'Student Grades',
                    'description' => 'View grades for a specific student.',
                    'route_name' => 'tenant.education.students.grades',
                    'priority' => 3,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'student-attendance',
                    'name' => 'Student Attendance',
                    'description' => 'View attendance records for a specific student.',
                    'route_name' => 'tenant.education.students.attendance',
                    'priority' => 4,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'student-financial-aid',
                    'name' => 'Financial Aid',
                    'description' => 'View student financial aid information.',
                    'route_name' => 'tenant.education.students.financial-aid',
                    'priority' => 5,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== FACULTY ====================
        [
            'code' => 'faculty',
            'name' => 'Faculty',
            'description' => 'Faculty member management and assignment.',
            'icon' => 'BriefcaseIcon',
            'route' => 'tenant.education.faculty.index',
            'priority' => 3,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'faculty-list',
                    'name' => 'Faculty List',
                    'description' => 'View and manage all faculty members.',
                    'route_name' => 'tenant.education.faculty.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== COURSES ====================
        [
            'code' => 'courses',
            'name' => 'Courses',
            'description' => 'Course catalog management with sections and scheduling.',
            'icon' => 'BookOpenIcon',
            'route' => 'tenant.education.courses.index',
            'priority' => 4,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'course-list',
                    'name' => 'Course List',
                    'description' => 'View and manage all courses.',
                    'route_name' => 'tenant.education.courses.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'course-sections',
                    'name' => 'Course Sections',
                    'description' => 'Manage sections within a course.',
                    'route_name' => 'tenant.education.courses.sections',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== ENROLLMENT ====================
        [
            'code' => 'enrollment',
            'name' => 'Enrollment',
            'description' => 'Student enrollment, drop, withdrawal, and waitlist management.',
            'icon' => 'ClipboardDocumentCheckIcon',
            'route' => 'tenant.education.enrollment.index',
            'priority' => 5,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'enrollment-list',
                    'name' => 'Enrollment List',
                    'description' => 'View and manage student enrollments.',
                    'route_name' => 'tenant.education.enrollment.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'enroll', 'name' => 'Enroll Student', 'is_active' => true],
                        ['code' => 'drop', 'name' => 'Drop Enrollment', 'is_active' => true],
                        ['code' => 'withdraw', 'name' => 'Withdraw Student', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'enrollment-waitlist',
                    'name' => 'Waitlist',
                    'description' => 'View and manage course waitlists.',
                    'route_name' => 'tenant.education.enrollment.waitlist',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== GRADES ====================
        [
            'code' => 'grades',
            'name' => 'Grades',
            'description' => 'Grade management by section and student.',
            'icon' => 'DocumentCheckIcon',
            'route' => 'tenant.education.grades.section',
            'priority' => 6,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'section-grades',
                    'name' => 'Section Grades',
                    'description' => 'View and update grades for a course section.',
                    'route_name' => 'tenant.education.grades.section',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'student-grade-report',
                    'name' => 'Student Grade Report',
                    'description' => 'View all grades for a specific student.',
                    'route_name' => 'tenant.education.grades.student',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== TRANSCRIPTS ====================
        [
            'code' => 'transcripts',
            'name' => 'Transcripts',
            'description' => 'Official transcript requests, generation, and management.',
            'icon' => 'DocumentTextIcon',
            'route' => 'tenant.education.transcripts.index',
            'priority' => 7,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'transcript-list',
                    'name' => 'Transcript List',
                    'description' => 'View and manage transcript requests.',
                    'route_name' => 'tenant.education.transcripts.index',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'request', 'name' => 'Request Transcript', 'is_active' => true],
                        ['code' => 'generate', 'name' => 'Generate Transcript', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== ATTENDANCE ====================
        [
            'code' => 'attendance',
            'name' => 'Attendance',
            'description' => 'Attendance tracking by section and student.',
            'icon' => 'CalendarDaysIcon',
            'route' => 'tenant.education.attendance.section',
            'priority' => 8,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'section-attendance',
                    'name' => 'Section Attendance',
                    'description' => 'View and take attendance for a course section.',
                    'route_name' => 'tenant.education.attendance.section',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Take Attendance', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'student-attendance-report',
                    'name' => 'Student Attendance Report',
                    'description' => 'View attendance records for a specific student.',
                    'route_name' => 'tenant.education.attendance.student',
                    'priority' => 2,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                    ],
                ],
            ],
        ],

        // ==================== ADMISSIONS ====================
        [
            'code' => 'admissions', 'name' => 'Admissions',
            'description' => 'Applications, entrance tests, merit list, seat allocation, enrolment.',
            'icon' => 'UserPlusIcon', 'route' => 'tenant.education.admissions.index', 'priority' => 9,
            'is_active' => true,
            'components' => [
                ['code' => 'applications', 'name' => 'Applications', 'route_name' => 'tenant.education.admissions.applications', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Application', 'is_active' => true],
                        ['code' => 'review', 'name' => 'Review Application', 'is_active' => true],
                        ['code' => 'approve', 'name' => 'Approve', 'is_active' => true],
                        ['code' => 'reject', 'name' => 'Reject', 'is_active' => true],
                    ]],
                ['code' => 'entrance-tests', 'name' => 'Entrance Tests', 'route_name' => 'tenant.education.admissions.tests', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Entrance Tests', 'is_active' => true]]],
                ['code' => 'merit-list', 'name' => 'Merit List', 'route_name' => 'tenant.education.admissions.merit', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'generate', 'name' => 'Generate Merit List', 'is_active' => true]]],
                ['code' => 'seat-allocation', 'name' => 'Seat Allocation', 'route_name' => 'tenant.education.admissions.seats', 'priority' => 4, 'is_active' => true,
                    'actions' => [['code' => 'allocate', 'name' => 'Allocate Seat', 'is_active' => true]]],
            ],
        ],

        // ==================== CURRICULUM ====================
        [
            'code' => 'curriculum', 'name' => 'Curriculum & Syllabus',
            'description' => 'Curriculum framework, syllabus, learning outcomes.',
            'icon' => 'BookOpenIcon', 'route' => 'tenant.education.curriculum.index', 'priority' => 10,
            'is_active' => true,
            'components' => [
                ['code' => 'curriculum-framework', 'name' => 'Curriculum Framework', 'route_name' => 'tenant.education.curriculum.framework', 'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Framework', 'is_active' => true]]],
                ['code' => 'syllabus', 'name' => 'Syllabus', 'route_name' => 'tenant.education.curriculum.syllabus', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Syllabus', 'is_active' => true]]],
                ['code' => 'learning-outcomes', 'name' => 'Learning Outcomes', 'route_name' => 'tenant.education.curriculum.outcomes', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Outcomes', 'is_active' => true]]],
            ],
        ],

        // ==================== TIMETABLE / SCHEDULING ====================
        [
            'code' => 'timetable', 'name' => 'Timetable & Scheduling',
            'description' => 'Class schedules, room allocation, teacher timetable.',
            'icon' => 'CalendarDaysIcon', 'route' => 'tenant.education.timetable.index', 'priority' => 11,
            'is_active' => true,
            'components' => [
                ['code' => 'class-timetable', 'name' => 'Class Timetable', 'route_name' => 'tenant.education.timetable.class', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'generate', 'name' => 'Auto-Generate Timetable', 'is_active' => true],
                        ['code' => 'manual-edit', 'name' => 'Manual Edit', 'is_active' => true],
                    ]],
                ['code' => 'teacher-timetable', 'name' => 'Teacher Timetable', 'route_name' => 'tenant.education.timetable.teacher', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View', 'is_active' => true]]],
                ['code' => 'room-allocation', 'name' => 'Room Allocation', 'route_name' => 'tenant.education.timetable.rooms', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Rooms', 'is_active' => true]]],
            ],
        ],

        // ==================== ASSIGNMENTS ====================
        [
            'code' => 'assignments', 'name' => 'Assignments',
            'description' => 'Create assignments, submissions, plagiarism check, grading.',
            'icon' => 'ClipboardDocumentListIcon', 'route' => 'tenant.education.assignments.index', 'priority' => 12,
            'is_active' => true,
            'components' => [
                ['code' => 'assignment-list', 'name' => 'Assignments', 'route_name' => 'tenant.education.assignments.index', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Assignment', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete', 'is_active' => true],
                        ['code' => 'grade', 'name' => 'Grade Submissions', 'is_active' => true],
                    ]],
                ['code' => 'submissions', 'name' => 'Submissions', 'route_name' => 'tenant.education.assignments.submissions', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View', 'is_active' => true], ['code' => 'submit', 'name' => 'Submit Assignment', 'is_active' => true]]],
                ['code' => 'plagiarism', 'name' => 'Plagiarism Check', 'route_name' => 'tenant.education.assignments.plagiarism', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'check', 'name' => 'Run Plagiarism Check', 'is_active' => true]]],
            ],
        ],

        // ==================== EXAMS ====================
        [
            'code' => 'exams', 'name' => 'Exams & Assessments',
            'description' => 'Exam schedules, question banks, online tests, results.',
            'icon' => 'DocumentCheckIcon', 'route' => 'tenant.education.exams.index', 'priority' => 13,
            'is_active' => true,
            'components' => [
                ['code' => 'exam-schedule', 'name' => 'Exam Schedule', 'route_name' => 'tenant.education.exams.schedule', 'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Exam Schedule', 'is_active' => true]]],
                ['code' => 'question-bank', 'name' => 'Question Bank', 'route_name' => 'tenant.education.exams.questions', 'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Question', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update Question', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete Question', 'is_active' => true],
                        ['code' => 'import', 'name' => 'Import Questions', 'is_active' => true],
                    ]],
                ['code' => 'online-tests', 'name' => 'Online Tests & Proctoring', 'route_name' => 'tenant.education.exams.online', 'priority' => 3, 'is_active' => true,
                    'actions' => [
                        ['code' => 'configure', 'name' => 'Configure Online Test', 'is_active' => true],
                        ['code' => 'launch', 'name' => 'Launch Test', 'is_active' => true],
                        ['code' => 'proctor', 'name' => 'Proctor Test', 'is_active' => true],
                    ]],
                ['code' => 'results', 'name' => 'Results & Marksheet', 'route_name' => 'tenant.education.exams.results', 'priority' => 4, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Results', 'is_active' => true],
                        ['code' => 'publish', 'name' => 'Publish Results', 'is_active' => true],
                        ['code' => 'generate-marksheet', 'name' => 'Generate Marksheet', 'is_active' => true],
                    ]],
            ],
        ],

        // ==================== FEES & BILLING ====================
        [
            'code' => 'fees-billing', 'name' => 'Fees & Billing',
            'description' => 'Fee structure, invoices, receipts, scholarships, financial aid.',
            'icon' => 'CurrencyDollarIcon', 'route' => 'tenant.education.fees.index', 'priority' => 14,
            'is_active' => true,
            'components' => [
                ['code' => 'fee-structure', 'name' => 'Fee Structure', 'route_name' => 'tenant.education.fees.structure', 'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Fee Structure', 'is_active' => true]]],
                ['code' => 'invoices', 'name' => 'Fee Invoices', 'route_name' => 'tenant.education.fees.invoices', 'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Invoices', 'is_active' => true],
                        ['code' => 'generate', 'name' => 'Generate Invoice', 'is_active' => true],
                        ['code' => 'send', 'name' => 'Send Invoice', 'is_active' => true],
                    ]],
                ['code' => 'receipts', 'name' => 'Fee Receipts', 'route_name' => 'tenant.education.fees.receipts', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Receipts', 'is_active' => true], ['code' => 'record', 'name' => 'Record Payment', 'is_active' => true]]],
                ['code' => 'scholarships', 'name' => 'Scholarships', 'route_name' => 'tenant.education.fees.scholarships', 'priority' => 4, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Scholarships', 'is_active' => true]]],
                ['code' => 'fee-defaulters', 'name' => 'Fee Defaulters', 'route_name' => 'tenant.education.fees.defaulters', 'priority' => 5, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Defaulters', 'is_active' => true], ['code' => 'remind', 'name' => 'Send Reminder', 'is_active' => true]]],
            ],
        ],

        // ==================== LIBRARY ====================
        [
            'code' => 'library', 'name' => 'Library',
            'description' => 'Book catalog, issue/return, digital library.',
            'icon' => 'BuildingLibraryIcon', 'route' => 'tenant.education.library.index', 'priority' => 15,
            'is_active' => true,
            'components' => [
                ['code' => 'catalog', 'name' => 'Book Catalog', 'route_name' => 'tenant.education.library.catalog', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Add Book', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update Book', 'is_active' => true],
                        ['code' => 'delete', 'name' => 'Delete Book', 'is_active' => true],
                    ]],
                ['code' => 'issue-return', 'name' => 'Issue & Return', 'route_name' => 'tenant.education.library.issue', 'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'issue', 'name' => 'Issue Book', 'is_active' => true],
                        ['code' => 'return', 'name' => 'Return Book', 'is_active' => true],
                        ['code' => 'renew', 'name' => 'Renew Book', 'is_active' => true],
                    ]],
                ['code' => 'digital-library', 'name' => 'Digital Library', 'route_name' => 'tenant.education.library.digital', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Digital Library', 'is_active' => true]]],
                ['code' => 'fines', 'name' => 'Library Fines', 'route_name' => 'tenant.education.library.fines', 'priority' => 4, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Fines', 'is_active' => true]]],
            ],
        ],

        // ==================== HOSTEL & TRANSPORT ====================
        [
            'code' => 'hostel-transport', 'name' => 'Hostel & Transport',
            'description' => 'Hostel allocation, transport routes, vehicle management.',
            'icon' => 'HomeModernIcon', 'route' => 'tenant.education.hostel.index', 'priority' => 16,
            'is_active' => true,
            'components' => [
                ['code' => 'hostels', 'name' => 'Hostels / Dormitories', 'route_name' => 'tenant.education.hostel.list', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'manage', 'name' => 'Manage Hostels', 'is_active' => true],
                        ['code' => 'allocate', 'name' => 'Allocate Room', 'is_active' => true],
                    ]],
                ['code' => 'transport', 'name' => 'Transport / Routes', 'route_name' => 'tenant.education.transport.routes', 'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Routes', 'is_active' => true],
                        ['code' => 'manage', 'name' => 'Manage Routes', 'is_active' => true],
                        ['code' => 'assign', 'name' => 'Assign Student to Route', 'is_active' => true],
                    ]],
                ['code' => 'vehicles', 'name' => 'Vehicles', 'route_name' => 'tenant.education.transport.vehicles', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Vehicles', 'is_active' => true]]],
            ],
        ],

        // ==================== VIRTUAL CLASSROOM & CONTENT ====================
        [
            'code' => 'virtual-classroom', 'name' => 'Virtual Classroom & Content',
            'description' => 'Live classes, SCORM/xAPI, content delivery, forums.',
            'icon' => 'VideoCameraIcon', 'route' => 'tenant.education.virtual.index', 'priority' => 17,
            'is_active' => true,
            'components' => [
                ['code' => 'live-classes', 'name' => 'Live Classes', 'route_name' => 'tenant.education.virtual.live', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Live Classes', 'is_active' => true],
                        ['code' => 'schedule', 'name' => 'Schedule Class', 'is_active' => true],
                        ['code' => 'join', 'name' => 'Join Class', 'is_active' => true],
                        ['code' => 'record', 'name' => 'Record Class', 'is_active' => true],
                    ]],
                ['code' => 'content-library', 'name' => 'Content Library (SCORM/xAPI)', 'route_name' => 'tenant.education.virtual.content', 'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View Content', 'is_active' => true],
                        ['code' => 'upload', 'name' => 'Upload Content', 'is_active' => true],
                        ['code' => 'assign', 'name' => 'Assign Content', 'is_active' => true],
                    ]],
                ['code' => 'forums', 'name' => 'Discussion Forums', 'route_name' => 'tenant.education.virtual.forums', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Forums', 'is_active' => true], ['code' => 'post', 'name' => 'Post', 'is_active' => true]]],
            ],
        ],

        // ==================== PORTALS (Parent / Student / Faculty) ====================
        [
            'code' => 'portals', 'name' => 'Parent / Student / Faculty Portals',
            'description' => 'Self-service portals for parents, students, faculty.',
            'icon' => 'UserCircleIcon', 'route' => 'tenant.education.portals.index', 'priority' => 18,
            'is_active' => true,
            'components' => [
                ['code' => 'parent-portal', 'name' => 'Parent Portal', 'route_name' => 'tenant.education.portals.parent', 'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'configure', 'name' => 'Configure Portal', 'is_active' => true]]],
                ['code' => 'student-portal', 'name' => 'Student Portal', 'route_name' => 'tenant.education.portals.student', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'configure', 'name' => 'Configure Portal', 'is_active' => true]]],
                ['code' => 'faculty-portal', 'name' => 'Faculty Portal', 'route_name' => 'tenant.education.portals.faculty', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'configure', 'name' => 'Configure Portal', 'is_active' => true]]],
            ],
        ],

        // ==================== COMMUNICATION ====================
        [
            'code' => 'communication', 'name' => 'Communication',
            'description' => 'Announcements, notices, SMS/email to students/parents.',
            'icon' => 'MegaphoneIcon', 'route' => 'tenant.education.communication.index', 'priority' => 19,
            'is_active' => true,
            'components' => [
                ['code' => 'announcements', 'name' => 'Announcements & Notices', 'route_name' => 'tenant.education.communication.announcements', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Create Announcement', 'is_active' => true],
                        ['code' => 'publish', 'name' => 'Publish', 'is_active' => true],
                    ]],
                ['code' => 'sms-email', 'name' => 'SMS / Email Blast', 'route_name' => 'tenant.education.communication.blast', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'send', 'name' => 'Send Blast', 'is_active' => true]]],
            ],
        ],

        // ==================== CORPORATE TRAINING ====================
        [
            'code' => 'corporate-training', 'name' => 'Corporate Training',
            'description' => 'B2B corporate training programs, compliance training, certifications.',
            'icon' => 'BriefcaseIcon', 'route' => 'tenant.education.corporate.index', 'priority' => 20,
            'is_active' => true,
            'components' => [
                ['code' => 'training-programs', 'name' => 'Training Programs', 'route_name' => 'tenant.education.corporate.programs', 'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Programs', 'is_active' => true]]],
                ['code' => 'certifications', 'name' => 'Certifications', 'route_name' => 'tenant.education.corporate.certifications', 'priority' => 2, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'issue', 'name' => 'Issue Certificate', 'is_active' => true],
                        ['code' => 'verify', 'name' => 'Verify Certificate', 'is_active' => true],
                    ]],
                ['code' => 'client-orgs', 'name' => 'Client Organizations', 'route_name' => 'tenant.education.corporate.clients', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Clients', 'is_active' => true]]],
            ],
        ],

        // ==================== ALUMNI ====================
        [
            'code' => 'alumni', 'name' => 'Alumni',
            'description' => 'Alumni database, events, donations.',
            'icon' => 'UserGroupIcon', 'route' => 'tenant.education.alumni.index', 'priority' => 21,
            'is_active' => true,
            'components' => [
                ['code' => 'alumni-list', 'name' => 'Alumni Directory', 'route_name' => 'tenant.education.alumni.list', 'priority' => 1, 'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'create', 'name' => 'Add Alumni', 'is_active' => true],
                        ['code' => 'update', 'name' => 'Update', 'is_active' => true],
                    ]],
                ['code' => 'events', 'name' => 'Alumni Events', 'route_name' => 'tenant.education.alumni.events', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Events', 'is_active' => true]]],
                ['code' => 'donations', 'name' => 'Donations', 'route_name' => 'tenant.education.alumni.donations', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Donations', 'is_active' => true]]],
            ],
        ],

        // ==================== RESEARCH MANAGEMENT ====================
        [
            'code' => 'research', 'name' => 'Research Management',
            'description' => 'Research projects, grants, publications.',
            'icon' => 'BeakerIcon', 'route' => 'tenant.education.research.index', 'priority' => 22,
            'is_active' => true,
            'components' => [
                ['code' => 'research-projects', 'name' => 'Research Projects', 'route_name' => 'tenant.education.research.projects', 'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Projects', 'is_active' => true]]],
                ['code' => 'grants', 'name' => 'Grants', 'route_name' => 'tenant.education.research.grants', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Grants', 'is_active' => true]]],
                ['code' => 'publications', 'name' => 'Publications', 'route_name' => 'tenant.education.research.publications', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Publications', 'is_active' => true]]],
            ],
        ],

        // ==================== REPORTS ====================
        [
            'code' => 'education-reports',
            'name' => 'Reports',
            'description' => 'Academic reports and analytics.',
            'icon' => 'ChartBarIcon',
            'route' => 'tenant.education.reports',
            'priority' => 23,
            'is_active' => true,
            'components' => [
                [
                    'code' => 'report-list',
                    'name' => 'Report List',
                    'description' => 'View available education reports.',
                    'route_name' => 'tenant.education.reports',
                    'priority' => 1,
                    'is_active' => true,
                    'actions' => [
                        ['code' => 'view', 'name' => 'View', 'is_active' => true],
                        ['code' => 'export', 'name' => 'Export', 'is_active' => true],
                    ],
                ],
                [
                    'code' => 'academic-reports', 'name' => 'Academic Reports', 'route_name' => 'tenant.education.reports.academic', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View', 'is_active' => true]],
                ],
                [
                    'code' => 'financial-reports', 'name' => 'Financial Reports', 'route_name' => 'tenant.education.reports.financial', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View', 'is_active' => true]],
                ],
            ],
        ],

        // ==================== SETTINGS ====================
        [
            'code' => 'education-settings', 'name' => 'Education Settings',
            'description' => 'Academic year, terms, grading scales.',
            'icon' => 'CogIcon', 'route' => 'tenant.education.settings.index', 'priority' => 99,
            'is_active' => true,
            'components' => [
                ['code' => 'academic-year', 'name' => 'Academic Year & Terms', 'route_name' => 'tenant.education.settings.academic-year', 'priority' => 1, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Academic Year', 'is_active' => true]]],
                ['code' => 'grading-scale', 'name' => 'Grading Scale', 'route_name' => 'tenant.education.settings.grading', 'priority' => 2, 'is_active' => true,
                    'actions' => [['code' => 'manage', 'name' => 'Manage Grading Scale', 'is_active' => true]]],
                ['code' => 'general', 'name' => 'General Settings', 'route_name' => 'tenant.education.settings.general', 'priority' => 3, 'is_active' => true,
                    'actions' => [['code' => 'view', 'name' => 'View Settings', 'is_active' => true], ['code' => 'update', 'name' => 'Update Settings', 'is_active' => true]]],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | EAM Integration Map
    |--------------------------------------------------------------------------
    */
    'eam_integration' => [
        'provides' => [
            'education.courses' => 'courses.course-list',
            'education.certifications' => 'corporate-training.certifications',
            'education.training_records' => 'corporate-training.training-programs',
        ],
        'consumes' => [
            'eam.asset_registry' => 'aero-eam',
            'hrm.training' => 'aero-hrm',
            'hrm.employees' => 'aero-hrm',
            'finance.fee_collection' => 'aero-finance',
            'real_estate.classrooms' => 'aero-real-estate',
        ],
    ],

    'access_control' => [
        'super_admin_role' => 'super-admin',
        'education_admin_role' => 'education-admin',
        'cache_ttl' => 3600,
        'cache_tags' => ['module-access', 'role-access', 'education-access'],
    ],
];
