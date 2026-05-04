<?php

declare(strict_types=1);

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\TagRequest;
use Aero\Core\Models\AuditLog;
use Aero\Core\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    /**
     * Display tag management interface.
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 20);

        $query = Tag::forTenant()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->withCount('records')
            ->orderBy('name');

        $tags = $query->paginate($perPage)->withQueryString();

        $counts = DB::table('taggables')
            ->select('taggable_type', DB::raw('count(distinct taggable_id) as count'))
            ->where('tenant_id', tenant('id'))
            ->groupBy('taggable_type')
            ->pluck('count', 'taggable_type')
            ->toArray();

        return Inertia::render('Core/Tags/Index', [
            'title' => 'Tags & Labels',
            'tags' => $tags,
            'counts' => $counts,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Store a new tag.
     */
    public function store(TagRequest $request): mixed
    {
        $tag = Tag::create([
            'tenant_id' => tenant('id'),
            ...$request->validated(),
        ]);

        $this->logTagAudit('created', $tag->id, "Tag '{$tag->name}' created", null, $tag->toArray());

        return redirect()->route('core.tags.index')
            ->with('success', "Tag '{$tag->name}' created successfully.");
    }

    /**
     * Update an existing tag.
     */
    public function update(TagRequest $request, Tag $tag): mixed
    {
        $old = $tag->toArray();
        $tag->update($request->validated());

        $this->logTagAudit('updated', $tag->id, "Tag '{$tag->name}' updated", $old, $tag->toArray());

        return redirect()->route('core.tags.index')
            ->with('success', "Tag '{$tag->name}' updated successfully.");
    }

    /**
     * Delete a tag.
     */
    public function destroy(Tag $tag): mixed
    {
        $name = $tag->name;
        $tag->delete();

        $this->logTagAudit('deleted', $tag->id, "Tag '{$name}' moved to trash", null, null);

        return redirect()->route('core.tags.index')
            ->with('success', "Tag '{$name}' moved to trash.");
    }

    /**
     * Display trashed tags.
     */
    public function trashed(Request $request): Response
    {
        $search = $request->get('search', '');
        $perPage = $request->get('per_page', 20);

        $query = Tag::onlyTrashed()
            ->forTenant()
            ->when($search !== '', fn ($q) => $q->search($search))
            ->orderBy('deleted_at', 'desc');

        $tags = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Core/Tags/Trashed', [
            'title' => 'Deleted Tags',
            'tags' => $tags,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Restore a soft-deleted tag.
     */
    public function restore(int $id): mixed
    {
        $tag = Tag::onlyTrashed()->forTenant()->findOrFail($id);
        $tag->restore();

        $this->logTagAudit('restored', $tag->id, "Tag '{$tag->name}' restored", null, null);

        return redirect()->route('core.tags.index')
            ->with('success', "Tag '{$tag->name}' restored successfully.");
    }

    /**
     * Permanently delete a soft-deleted tag.
     */
    public function forceDelete(int $id): mixed
    {
        $tag = Tag::onlyTrashed()->forTenant()->findOrFail($id);
        $name = $tag->name;
        $tag->forceDelete();

        $this->logTagAudit('force_deleted', $tag->id, "Tag '{$name}' permanently deleted", null, null);

        return redirect()->route('core.tags.trashed')
            ->with('success', "Tag '{$name}' permanently deleted.");
    }

    /**
     * Merge source tag into target tag.
     * Moves all taggable associations from source to target, then deletes source.
     */
    public function merge(Request $request): JsonResponse
    {
        $request->validate([
            'source_tag_id' => ['required', 'integer', 'exists:tags,id'],
            'target_tag_id' => ['required', 'integer', 'exists:tags,id', 'different:source_tag_id'],
        ]);

        $tenantId = tenant('id');
        $source = Tag::forTenant($tenantId)->findOrFail($request->input('source_tag_id'));
        $target = Tag::forTenant($tenantId)->findOrFail($request->input('target_tag_id'));

        DB::transaction(function () use ($source, $target, $tenantId) {
            $existing = DB::table('taggables')
                ->where('tag_id', $target->id)
                ->where('tenant_id', $tenantId)
                ->pluck('taggable_type', 'taggable_id')
                ->mapWithKeys(fn ($type, $id) => ["{$type}:{$id}" => true])
                ->toArray();

            $sourceAssociations = DB::table('taggables')
                ->where('tag_id', $source->id)
                ->where('tenant_id', $tenantId)
                ->get();

            foreach ($sourceAssociations as $assoc) {
                $key = "{$assoc->taggable_type}:{$assoc->taggable_id}";
                if (! isset($existing[$key])) {
                    DB::table('taggables')->insert([
                        'tag_id' => $target->id,
                        'tenant_id' => $tenantId,
                        'taggable_type' => $assoc->taggable_type,
                        'taggable_id' => $assoc->taggable_id,
                        'created_by' => $assoc->created_by,
                        'created_at' => now(),
                    ]);
                    $existing[$key] = true;
                }
            }

            DB::table('taggables')
                ->where('tag_id', $source->id)
                ->where('tenant_id', $tenantId)
                ->delete();

            $source->delete();
        });

        $this->logTagAudit('merged', $target->id, "Tag '{$source->name}' merged into '{$target->name}'", null, ['source_tag_id' => $source->id, 'target_tag_id' => $target->id]);

        return response()->json([
            'message' => "Tag '{$source->name}' merged into '{$target->name}' successfully.",
            'target' => $target,
        ]);
    }

    /**
     * Bulk assign or remove tags from multiple records.
     */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'in:assign,remove'],
            'tag_ids' => ['required', 'array', 'min:1'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.type' => ['required', 'string'],
            'records.*.id' => ['required', 'integer'],
        ]);

        $tenantId = tenant('id');
        $tagIds = $request->input('tag_ids');

        DB::transaction(function () use ($request, $tagIds, $tenantId) {
            foreach ($request->input('records') as $record) {
                $model = $record['type']::find($record['id']);
                if (! $model) {
                    continue;
                }

                if ($request->input('action') === 'assign') {
                    foreach ($tagIds as $tagId) {
                        $exists = DB::table('taggables')
                            ->where('tag_id', $tagId)
                            ->where('taggable_type', $record['type'])
                            ->where('taggable_id', $record['id'])
                            ->where('tenant_id', $tenantId)
                            ->exists();

                        if (! $exists) {
                            DB::table('taggables')->insert([
                                'tag_id' => $tagId,
                                'tenant_id' => $tenantId,
                                'taggable_type' => $record['type'],
                                'taggable_id' => $record['id'],
                                'created_by' => auth()->id(),
                                'created_at' => now(),
                            ]);
                        }
                    }
                } else {
                    DB::table('taggables')
                        ->whereIn('tag_id', $tagIds)
                        ->where('taggable_type', $record['type'])
                        ->where('taggable_id', $record['id'])
                        ->where('tenant_id', $tenantId)
                        ->delete();
                }
            }
        });

        $this->logTagAudit('bulk_' . $request->input('action'), null, 'Bulk tag ' . $request->input('action') . ' on ' . count($request->input('records')) . ' records', null, ['tag_ids' => $tagIds, 'records' => $request->input('records')]);

        return response()->json([
            'message' => 'Bulk tag ' . $request->input('action') . ' completed successfully.',
        ]);
    }

    /**
     * Aggregate counts per taggable type (JSON API).
     */
    public function taggableCounts(Request $request): JsonResponse
    {
        $counts = DB::table('taggables')
            ->select('taggable_type', DB::raw('count(distinct taggable_id) as count'))
            ->where('tenant_id', tenant('id'))
            ->groupBy('taggable_type')
            ->get();

        return response()->json($counts);
    }

    /**
     * Export tags as CSV.
     */
    public function export(Request $request): mixed
    {
        $tags = Tag::forTenant()->orderBy('name')->get(['name', 'slug', 'color', 'description', 'icon']);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tags-'.tenant('id').'-'.now()->format('YmdHis').'.csv"',
        ];

        $callback = function () use ($tags) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['name', 'slug', 'color', 'description', 'icon']);
            foreach ($tags as $tag) {
                fputcsv($file, [$tag->name, $tag->slug, $tag->color, $tag->description, $tag->icon]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import tags from CSV.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return response()->json(['message' => 'Empty CSV file.'], 422);
        }

        $expected = ['name', 'slug', 'color', 'description', 'icon'];
        if (array_map('strtolower', $header) !== $expected) {
            fclose($handle);

            return response()->json(['message' => 'Invalid CSV header. Expected: name, slug, color, description, icon'], 422);
        }

        $tenantId = tenant('id');
        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 5) {
                continue;
            }
            [$name, $slug, $color, $description, $icon] = array_pad($row, 5, null);
            if (empty($name)) {
                continue;
            }

            $slug = $slug ?: Str::slug($name);

            $exists = Tag::forTenant($tenantId)->where('slug', $slug)->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            Tag::create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'slug' => $slug,
                'color' => $color ?: '#0ea5e9',
                'description' => $description,
                'icon' => $icon,
            ]);
            $imported++;
        }

        fclose($handle);

        if ($imported > 0) {
            $this->logTagAudit('imported', null, "Imported {$imported} tags ({$skipped} skipped)", null, ['imported' => $imported, 'skipped' => $skipped]);
        }

        return response()->json([
            'message' => "Import completed: {$imported} tags created, {$skipped} skipped (already exist).",
            'imported' => $imported,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Log a tag-related audit entry.
     */
    private function logTagAudit(string $action, ?int $tagId, string $description, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name ?? 'System',
            'user_email' => auth()->user()?->email ?? '',
            'action' => $action,
            'auditable_type' => $tagId ? Tag::class : 'Tag',
            'auditable_id' => $tagId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => ['tenant_id' => tenant('id')],
        ]);
    }
}
