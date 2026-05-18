<?php

declare(strict_types=1);

namespace Aero\HRM\Services\Analytics;

use Aero\HRM\Models\PulseSurvey;

class PulseSurveyService
{
    /**
     * Aggregate response data for each question in a survey.
     *
     * For scale-type questions: compute average.
     * For choice-type questions: count distribution.
     *
     * @return array<int|string, array{average?: float, distribution?: array<string, int>}>
     */
    public function aggregate(PulseSurvey $survey): array
    {
        $responses = $survey->responses()->get(['answers']);
        $questions = $survey->questions ?? [];
        $result = [];

        foreach ($questions as $question) {
            $qid = $question['id'];
            $type = $question['type'] ?? 'scale';

            // Collect all answers for this question
            $values = $responses
                ->map(fn ($r) => collect($r->answers)->firstWhere('question_id', $qid))
                ->filter()
                ->pluck('value')
                ->all();

            if ($type === 'scale') {
                $numeric = array_filter($values, fn ($v) => is_numeric($v));
                $result[$qid] = [
                    'average' => count($numeric) > 0
                        ? round(array_sum($numeric) / count($numeric), 2)
                        : null,
                ];
            } else {
                // choice / multiple-choice
                $dist = [];
                foreach ($values as $v) {
                    $key = (string) $v;
                    $dist[$key] = ($dist[$key] ?? 0) + 1;
                }
                $result[$qid] = ['distribution' => $dist];
            }
        }

        return $result;
    }

    /**
     * Generate an anonymised respondent hash.
     */
    public function generateRespondentHash(int $surveyId, int $employeeId): string
    {
        return hash('sha256', "{$surveyId}:{$employeeId}:".config('app.key'));
    }
}
