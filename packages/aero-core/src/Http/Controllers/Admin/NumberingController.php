<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NumberingController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        $sequences = DB::table('numbering_sequences')->orderBy('entity_type')->get();
        $formats = DB::table('numbering_formats')->orderBy('name')->get();

        return Inertia::render('Core/Numbering/Index', [
            'sequences' => $sequences,
            'formats' => $formats,
        ]);
    }

    public function storeSequence(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entity_type' => ['required', 'string', 'max:100'],
            'prefix' => ['nullable', 'string', 'max:20'],
            'next_value' => ['required', 'integer', 'min:1'],
            'padding' => ['integer', 'min:1', 'max:10'],
            'format_id' => ['nullable', 'integer'],
        ]);

        DB::table('numbering_sequences')->updateOrInsert(
            ['entity_type' => $data['entity_type']],
            array_merge($data, ['updated_at' => now(), 'created_at' => now()])
        );

        $this->audit->log(AuditEventType::RECORD_CREATED->value, 'sequence_saved', null, 'Numbering sequence saved');

        return back()->with('success', 'Sequence saved.');
    }

    public function resetSequence(string $entityType, Request $request): RedirectResponse
    {
        $request->validate(['next_value' => ['required', 'integer', 'min:1']]);
        DB::table('numbering_sequences')->where('entity_type', $entityType)->update([
            'next_value' => $request->next_value,
            'updated_at' => now(),
        ]);
        $this->audit->log(AuditEventType::RECORD_UPDATED->value, 'sequence_reset', null, "Sequence reset: {$entityType}");

        return back()->with('success', "Sequence reset to {$request->next_value}.");
    }

    public function storeFormat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'pattern' => ['required', 'string', 'max:100'],
            'example' => ['nullable', 'string', 'max:100'],
        ]);

        DB::table('numbering_formats')->insertGetId(array_merge($data, [
            'created_at' => now(), 'updated_at' => now(),
        ]));

        $this->audit->log(AuditEventType::RECORD_CREATED->value, 'format_created', null, 'Number format created');

        return back()->with('success', 'Format created.');
    }

    public function destroyFormat(int $id, Request $request): RedirectResponse
    {
        DB::table('numbering_formats')->delete($id);
        $this->audit->log(AuditEventType::RECORD_DELETED->value, 'format_deleted', null, "Format deleted: {$id}");

        return back()->with('success', 'Format deleted.');
    }
}
