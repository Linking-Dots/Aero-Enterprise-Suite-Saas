<?php

declare(strict_types=1);

namespace Aero\Contracts\Ai;

/**
 * A read tool Aeon can call to answer data questions with generative-UI blocks.
 * Feature packages implement this and tag it 'aeon.tools' so Aeon can discover it
 * — e.g. an HRM attendance-summary tool that returns stat + chart blocks.
 *
 * run() returns deterministic content (no model hallucination over the numbers):
 *   ['text' => string, 'blocks' => array<int,array<string,mixed>>]
 * where blocks use Aeon's renderer types (stats, chart, table, entityCard, chips).
 */
interface AeonToolContract
{
    /** Function name the model calls (snake_case, unique). */
    public function name(): string;

    /** When the model should call this tool. */
    public function description(): string;

    /**
     * JSON-schema "properties" for the tool arguments (empty if none).
     *
     * @return array<string,mixed>
     */
    public function parameters(): array;

    /**
     * Execute the tool for the given user.
     *
     * May additionally return a 'data' key: a compact machine-readable payload
     * (raw rows / aggregates / ids) that is fed BACK to the model as the tool
     * result so it can reason over real data and chain further tool calls.
     *
     * @param  array<string,mixed>  $args
     * @return array{text:string,blocks:array<int,array<string,mixed>>,data?:array<string,mixed>}
     */
    public function run(array $args, ?int $userId): array;
}
