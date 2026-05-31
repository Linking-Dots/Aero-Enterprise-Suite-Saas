<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PrintTemplateController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        return Inertia::render('Core/PrintTemplates/Index', [
            'templates' => DB::table('print_templates')->orderBy('name')->get(),
            'paper_sizes' => $this->getDefaultPaperSizes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'entity_type' => ['required', 'string'],
            'template' => ['required', 'string'],
            'paper_size' => ['required', 'in:A4,Letter,Legal,A3'],
            'orientation' => ['required', 'in:portrait,landscape'],
            'margin_top' => ['numeric', 'min:0'],
            'margin_right' => ['numeric', 'min:0'],
            'margin_bottom' => ['numeric', 'min:0'],
            'margin_left' => ['numeric', 'min:0'],
            'header_html' => ['nullable', 'string'],
            'footer_html' => ['nullable', 'string'],
            'is_default' => ['boolean'],
        ]);

        if (! empty($data['is_default'])) {
            DB::table('print_templates')
                ->where('entity_type', $data['entity_type'])
                ->update(['is_default' => false]);
        }

        $id = DB::table('print_templates')->insertGetId(array_merge($data, [
            'created_at' => now(), 'updated_at' => now(),
        ]));

        $this->audit->log(AuditEventType::RECORD_CREATED->value, 'created', null, 'Print template created', null, null, ['id' => $id]);

        return back()->with('success', 'Print template created.');
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'template' => ['sometimes', 'required', 'string'],
            'paper_size' => ['sometimes', 'required', 'in:A4,Letter,Legal,A3'],
            'orientation' => ['sometimes', 'required', 'in:portrait,landscape'],
            'header_html' => ['nullable', 'string'],
            'footer_html' => ['nullable', 'string'],
            'is_default' => ['boolean'],
        ]);

        DB::table('print_templates')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        $this->audit->log(AuditEventType::RECORD_UPDATED->value, 'updated', null, 'Print template updated', null, null, ['id' => $id]);

        return back()->with('success', 'Template updated.');
    }

    public function destroy(int $id, Request $request): RedirectResponse
    {
        DB::table('print_templates')->delete($id);
        $this->audit->log(AuditEventType::RECORD_DELETED->value, 'deleted', null, 'Print template deleted', null, null, ['id' => $id]);

        return back()->with('success', 'Template deleted.');
    }

    public function preview(int $id): HttpResponse
    {
        $tpl = DB::table('print_templates')->find($id);
        if (! $tpl) {
            abort(404);
        }

        return response($tpl->template ?? '<p>No template content</p>')->header('Content-Type', 'text/html');
    }

    private function getDefaultPaperSizes(): array
    {
        return [
            ['id' => 'A4',     'label' => 'A4 (210 × 297mm)'],
            ['id' => 'Letter', 'label' => 'US Letter (8.5 × 11in)'],
            ['id' => 'Legal',  'label' => 'US Legal (8.5 × 14in)'],
            ['id' => 'A3',     'label' => 'A3 (297 × 420mm)'],
        ];
    }
}
