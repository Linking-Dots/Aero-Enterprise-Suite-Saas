<?php

declare(strict_types=1);

namespace Aero\Assistant\Tools;

use Aero\Contracts\Ai\AeonToolContract;
use Illuminate\Support\Carbon;

/**
 * Answers "how many users / accounts are there?" with real numbers rendered as
 * generative-UI blocks (stat tiles + a 7-day new-users sparkline). Deterministic
 * — the figures come from the database, never from the model. Uses the configured
 * auth user model, so it works in every install without coupling to a module.
 */
class UserStatsTool implements AeonToolContract
{
    public function name(): string
    {
        return 'user_stats';
    }

    public function description(): string
    {
        return 'Get real counts of users/accounts in this workspace — total users and how many joined '
            .'in the last 7 days. Call this whenever the user asks how many users/people/accounts there '
            .'are, or about user growth or sign-ups.';
    }

    public function parameters(): array
    {
        return [];
    }

    public function run(array $args, ?int $userId): array
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $model */
        $model = config('auth.providers.users.model');

        $total = (int) $model::query()->count();

        // New users per day over the last 7 days (inclusive of today).
        $start = Carbon::today()->subDays(6);
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[Carbon::today()->subDays(6 - $i)->format('Y-m-d')] = 0;
        }
        $recent = $model::query()
            ->where('created_at', '>=', $start->startOfDay())
            ->get(['created_at']);
        foreach ($recent as $row) {
            $key = optional($row->created_at)->format('Y-m-d');
            if ($key !== null && array_key_exists($key, $days)) {
                $days[$key]++;
            }
        }
        $series = array_values($days);
        $newThisWeek = array_sum($series);

        $text = $newThisWeek > 0
            ? "You have **{$total} users**, with **{$newThisWeek}** joining in the last 7 days."
            : "You have **{$total} users**. No new sign-ups in the last 7 days.";

        return [
            'text' => $text,
            'blocks' => [
                ['type' => 'stats', 'items' => [
                    ['k' => 'Total users', 'v' => (string) $total],
                    ['k' => 'New · 7 days', 'v' => (string) $newThisWeek],
                ]],
                ['type' => 'chart', 'title' => 'New users · last 7 days', 'points' => $series],
            ],
        ];
    }
}
