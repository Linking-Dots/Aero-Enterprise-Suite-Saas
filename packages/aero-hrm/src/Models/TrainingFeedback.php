<?php

namespace Aero\HRM\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingFeedback extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'training_id',
        'user_id',
        'rating',
        'feedback',
        'instructor_rating',
        'content_rating',
        'materials_rating',
        'organization_rating',
        'relevance_rating',
        'recommendation_likelihood',
        'submitted_at',
        'is_anonymous',
    ];

    protected $casts = [
        'rating' => 'float',
        'instructor_rating' => 'float',
        'content_rating' => 'float',
        'materials_rating' => 'float',
        'organization_rating' => 'float',
        'relevance_rating' => 'float',
        'recommendation_likelihood' => 'float',
        'submitted_at' => 'datetime',
        'is_anonymous' => 'boolean',
    ];

    /**
     * Get the training this feedback is for.
     */
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Get the user who submitted the feedback.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if the feedback is anonymous.
     */
    public function isAnonymous()
    {
        return $this->is_anonymous;
    }

    /**
     * Get the average rating across all categories.
     */
    public function getAverageRating()
    {
        $ratings = [
            $this->instructor_rating,
            $this->content_rating,
            $this->materials_rating,
            $this->organization_rating,
            $this->relevance_rating,
        ];

        $validRatings = array_filter($ratings, function ($rating) {
            return $rating !== null;
        });

        if (empty($validRatings)) {
            return null;
        }

        return array_sum($validRatings) / count($validRatings);
    }
}
