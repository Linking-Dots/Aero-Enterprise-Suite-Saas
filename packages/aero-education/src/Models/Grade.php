<?php

namespace Aero\Education\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'education_grades';

    protected $fillable = [
        'student_id', 'enrollment_id', 'assignment_id', 'grade_type',
        'points_earned', 'points_possible', 'percentage', 'letter_grade',
        'grade_points', 'credit_hours', 'is_final', 'affects_gpa',
        'submission_date', 'graded_date', 'graded_by', 'feedback',
        'late_penalty', 'extra_credit', 'created_by',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'enrollment_id' => 'integer',
        'assignment_id' => 'integer',
        'points_earned' => 'decimal:2',
        'points_possible' => 'decimal:2',
        'percentage' => 'decimal:2',
        'grade_points' => 'decimal:3',
        'credit_hours' => 'integer',
        'is_final' => 'boolean',
        'affects_gpa' => 'boolean',
        'submission_date' => 'datetime',
        'graded_date' => 'datetime',
        'graded_by' => 'integer',
        'late_penalty' => 'decimal:2',
        'extra_credit' => 'decimal:2',
        'created_by' => 'integer',
    ];

    const TYPE_ASSIGNMENT = 'assignment';

    const TYPE_QUIZ = 'quiz';

    const TYPE_EXAM = 'exam';

    const TYPE_MIDTERM = 'midterm';

    const TYPE_FINAL = 'final';

    const TYPE_PROJECT = 'project';

    const TYPE_PARTICIPATION = 'participation';

    const TYPE_LAB = 'lab';

    const TYPE_HOMEWORK = 'homework';

    const TYPE_PAPER = 'paper';

    const TYPE_PRESENTATION = 'presentation';

    const LETTER_A_PLUS = 'A+';

    const LETTER_A = 'A';

    const LETTER_A_MINUS = 'A-';

    const LETTER_B_PLUS = 'B+';

    const LETTER_B = 'B';

    const LETTER_B_MINUS = 'B-';

    const LETTER_C_PLUS = 'C+';

    const LETTER_C = 'C';

    const LETTER_C_MINUS = 'C-';

    const LETTER_D_PLUS = 'D+';

    const LETTER_D = 'D';

    const LETTER_D_MINUS = 'D-';

    const LETTER_F = 'F';

    const LETTER_I = 'I'; // Incomplete

    const LETTER_W = 'W'; // Withdrawn

    const LETTER_P = 'P'; // Pass

    const LETTER_NP = 'NP'; // No Pass

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function gradedBy()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calculatePercentage()
    {
        if ($this->points_possible > 0) {
            $adjustedPoints = $this->points_earned + ($this->extra_credit ?? 0) - ($this->late_penalty ?? 0);

            return round(($adjustedPoints / $this->points_possible) * 100, 2);
        }

        return 0;
    }

    public function calculateLetterGrade($percentage = null)
    {
        $percentage = $percentage ?? $this->percentage ?? $this->calculatePercentage();

        return match (true) {
            $percentage >= 97 => self::LETTER_A_PLUS,
            $percentage >= 93 => self::LETTER_A,
            $percentage >= 90 => self::LETTER_A_MINUS,
            $percentage >= 87 => self::LETTER_B_PLUS,
            $percentage >= 83 => self::LETTER_B,
            $percentage >= 80 => self::LETTER_B_MINUS,
            $percentage >= 77 => self::LETTER_C_PLUS,
            $percentage >= 73 => self::LETTER_C,
            $percentage >= 70 => self::LETTER_C_MINUS,
            $percentage >= 67 => self::LETTER_D_PLUS,
            $percentage >= 63 => self::LETTER_D,
            $percentage >= 60 => self::LETTER_D_MINUS,
            default => self::LETTER_F
        };
    }

    public function calculateGradePoints($letterGrade = null)
    {
        $letterGrade = $letterGrade ?? $this->letter_grade;

        return match ($letterGrade) {
            self::LETTER_A_PLUS, self::LETTER_A => 4.0,
            self::LETTER_A_MINUS => 3.7,
            self::LETTER_B_PLUS => 3.3,
            self::LETTER_B => 3.0,
            self::LETTER_B_MINUS => 2.7,
            self::LETTER_C_PLUS => 2.3,
            self::LETTER_C => 2.0,
            self::LETTER_C_MINUS => 1.7,
            self::LETTER_D_PLUS => 1.3,
            self::LETTER_D => 1.0,
            self::LETTER_D_MINUS => 0.7,
            self::LETTER_F => 0.0,
            self::LETTER_P => null, // Pass/No Pass doesn't affect GPA
            self::LETTER_NP => null,
            default => 0.0
        };
    }

    public function isPassingGrade()
    {
        return ! in_array($this->letter_grade, [self::LETTER_F, self::LETTER_NP]);
    }

    public function isLate()
    {
        if (! $this->assignment || ! $this->submission_date) {
            return false;
        }

        return $this->submission_date > $this->assignment->due_date;
    }

    public function getAdjustedScoreAttribute()
    {
        return $this->points_earned + ($this->extra_credit ?? 0) - ($this->late_penalty ?? 0);
    }

    public function getGradeDisplayAttribute()
    {
        if ($this->letter_grade) {
            return $this->letter_grade.' ('.number_format($this->percentage, 1).'%)';
        }

        return number_format($this->percentage, 1).'%';
    }

    public function autoGrade()
    {
        $this->percentage = $this->calculatePercentage();
        $this->letter_grade = $this->calculateLetterGrade($this->percentage);
        $this->grade_points = $this->calculateGradePoints($this->letter_grade);
        $this->graded_date = now();
        $this->save();
    }

    public function scopeFinalGrades($query)
    {
        return $query->where('is_final', true);
    }

    public function scopeByGradeType($query, $type)
    {
        return $query->where('grade_type', $type);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopePassing($query)
    {
        return $query->whereNotIn('letter_grade', [self::LETTER_F, self::LETTER_NP]);
    }

    public function scopeAffectsGPA($query)
    {
        return $query->where('affects_gpa', true);
    }
}
