# Aero Education Management System

A comprehensive Student Information System (SIS) and Learning Management System (LMS) for educational institutions, from K-12 schools to universities.

## Features

### Student Information System (SIS)
- **Student Profiles**: Complete student records with encrypted PII data
- **Academic Records**: GPA tracking, credit hours, academic standing
- **Enrollment Management**: Course registration, waitlists, add/drop periods
- **Transcript Management**: Official and unofficial transcript generation
- **Financial Aid**: Scholarship, grant, and loan management with automated disbursements

### Faculty & Staff Management
- **Faculty Profiles**: Complete faculty information with specializations and credentials
- **Academic Hierarchy**: College → Department → Faculty organization
- **Teaching Assignments**: Course section assignments and teaching load tracking
- **Academic Advising**: Student-advisor relationships and advising tracking

### Course & Curriculum Management
- **Course Catalog**: Comprehensive course information with prerequisites
- **Academic Programs**: Degree programs with graduation requirements
- **Course Sections**: Multiple sections per course with enrollment limits
- **Prerequisites**: Automated prerequisite checking and validation

### Academic Assessment
- **Grading System**: Flexible grading scales with GPA calculation
- **Assignment Management**: Assignment creation, submission, and grading
- **Grade Books**: Faculty grade book with multiple assessment types
- **Academic Progress**: Progress tracking and early warning systems

### Attendance Management
- **Attendance Tracking**: Daily attendance recording with multiple status options
- **Excuse Management**: Absence excuse requests and approval workflow
- **Attendance Analytics**: Attendance patterns and intervention alerts
- **Automated Notifications**: Low attendance alerts for students and advisors

### Academic Calendar & Scheduling
- **Semester Management**: Academic calendar with registration periods
- **Class Scheduling**: Course section scheduling with room assignments
- **Academic Deadlines**: Add/drop, withdrawal, and payment deadlines
- **Holiday Management**: Academic calendar with institutional holidays

## Models Overview

### Core Academic Models
- `Student`: Complete student information system with encrypted PII
- `Faculty`: Faculty management with specializations and credentials
- `Course`: Course catalog with prerequisites and requirements
- `CourseSection`: Course sections with enrollment and scheduling
- `Enrollment`: Student course enrollments with status tracking

### Academic Assessment
- `Grade`: Comprehensive grading system with multiple assessment types
- `Assignment`: Assignment management with due dates and rubrics
- `Transcript`: Official transcript generation and management
- `AcademicProgram`: Degree program requirements and tracking

### Institutional Organization
- `College`: College/school organization within institution
- `Department`: Academic departments with faculty assignments
- `AcademicSemester`: Semester/term management with deadlines

### Support Services
- `FinancialAid`: Financial aid awards and disbursement tracking
- `AttendanceRecord`: Comprehensive attendance tracking and management

## Key Features

### Multi-Institution Support
- Tenant-isolated data for multiple institutions
- Configurable academic policies per institution
- Role-based access control

### Academic Compliance
- FERPA compliance for student record privacy
- Academic progress monitoring
- Retention and graduation tracking
- Accreditation reporting

### Advanced Analytics
- Academic performance analytics
- Enrollment trend analysis
- Faculty workload distribution
- Student success metrics

### Integration Ready
- LMS integration capabilities
- Financial system integration
- Library system integration
- Parent portal connectivity

## Installation

```bash
composer require aero/education
```

## Configuration

```php
// config/education.php
return [
    'academic' => [
        'default_credit_hours' => 3,
        'max_credit_hours_per_semester' => 18,
        'gpa_scale' => 4.0,
    ],
    'grading' => [
        'scale' => [...], // Customizable grading scale
    ],
    'enrollment' => [
        'add_drop_deadline_weeks' => 2,
        'withdrawal_deadline_weeks' => 10,
    ],
];
```

## Usage Examples

### Student Management
```php
// Create a student
$student = Student::create([
    'student_id' => 'STU2024001',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@university.edu',
    'admission_date' => '2024-08-15',
    'academic_level' => Student::LEVEL_UNDERGRADUATE,
    'major' => 'Computer Science',
]);

// Enroll in a course
$section = CourseSection::find(1);
$enrollment = $section->addStudent($student);
```

### Course Management
```php
// Create a course with prerequisites
$course = Course::create([
    'course_code' => 'CS101',
    'course_name' => 'Introduction to Computer Science',
    'department_id' => $department->id,
    'credit_hours' => 3,
    'prerequisites' => [
        'MATH101', // Math prerequisite
    ],
]);

// Create course section
$section = $course->sections()->create([
    'semester_id' => $currentSemester->id,
    'section_number' => '001',
    'instructor_id' => $faculty->id,
    'max_enrollment' => 30,
]);
```

### Grading System
```php
// Create assignment
$assignment = $section->assignments()->create([
    'assignment_name' => 'Midterm Exam',
    'assignment_type' => Assignment::TYPE_EXAM,
    'points_possible' => 100,
    'due_date' => '2024-10-15 23:59:59',
]);

// Record grade
$grade = $enrollment->grades()->create([
    'assignment_id' => $assignment->id,
    'points_earned' => 85,
    'points_possible' => 100,
    'grade_type' => Grade::TYPE_EXAM,
]);

$grade->autoGrade(); // Calculate percentage, letter grade, and GPA points
```

### Financial Aid Management
```php
// Award financial aid
$aid = $student->financialAid()->create([
    'aid_type' => FinancialAid::TYPE_SCHOLARSHIP,
    'aid_source' => FinancialAid::SOURCE_INSTITUTIONAL,
    'award_name' => 'Academic Excellence Scholarship',
    'awarded_amount' => 5000.00,
    'academic_year' => '2024-2025',
    'renewable' => true,
    'gpa_requirement' => 3.5,
]);

// Check renewal eligibility
if ($aid->isRenewable() && $aid->meetsRenewalCriteria()) {
    // Process renewal
}
```

### Transcript Generation
```php
// Request official transcript
$transcript = $student->transcripts()->create([
    'transcript_type' => Transcript::TYPE_OFFICIAL,
    'delivery_method' => Transcript::DELIVERY_EMAIL,
    'recipient_email' => 'admissions@graduate-school.edu',
]);

// Generate transcript
$transcript->generateTranscript();
```

## API Endpoints

### Students
- `GET /education/students` - List students
- `POST /education/students` - Create student
- `GET /education/students/{id}` - Get student details
- `GET /education/students/{id}/transcript` - Student transcript
- `GET /education/students/{id}/grades` - Student grades

### Courses & Enrollment
- `GET /education/courses` - List courses
- `POST /education/enrollment/enroll` - Enroll student
- `POST /education/enrollment/drop` - Drop course
- `GET /education/grades/section/{id}` - Section gradebook

### Faculty
- `GET /education/faculty` - List faculty
- `GET /education/faculty/{id}` - Faculty details
- `GET /education/faculty/{id}/sections` - Teaching assignments

## Advanced Features

### Academic Analytics Dashboard
- Student performance metrics
- Course completion rates
- Faculty teaching effectiveness
- Enrollment trends and forecasting

### Early Warning System
- At-risk student identification
- Automated intervention triggers
- Academic progress monitoring
- Retention analysis

### Compliance & Reporting
- FERPA compliance features
- Academic progress reports
- Accreditation data collection
- Financial aid compliance tracking

### Parent/Guardian Portal
- Student progress monitoring
- Grade and attendance access
- Communication with faculty
- Financial information access

## License

This package is part of the aeos365 and is proprietary software.