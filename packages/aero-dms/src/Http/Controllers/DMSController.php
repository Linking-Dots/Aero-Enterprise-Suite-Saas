<?php

namespace Aero\Dms\Http\Controllers;

use Aero\Dms\Models\Category;
use Aero\Dms\Models\Document;
use Aero\Dms\Models\DocumentVersion;
use Aero\Dms\Models\Folder;
use Aero\Dms\Services\DMSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DMSController extends Controller
{
    public function __construct(
        protected DMSService $dmsService,
    ) {}

    /**
     * Display the DMS dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        $statistics = $this->dmsService->getStatistics($user);
        $recentActivity = $this->dmsService->getRecentActivity(10);

        // Dynamic widgets removed — widget registry is being deleted
        $dynamicWidgets = [];

        return Inertia::render('DMS/Dashboard/Index', [
            'statistics' => $statistics,
            'recentActivity' => $recentActivity,
            'categories' => Category::active()->get(),
            'dynamicWidgets' => $dynamicWidgets,
        ]);
    }

    /**
     * List all documents.
     */
    public function documents(Request $request)
    {
        $user = Auth::user();
        $filters = $request->only(['search', 'category_id', 'status', 'visibility', 'folder_id']);

        $documents = $this->dmsService->getAccessibleDocuments($user, $filters)
            ->latestVersions()
            ->latest()
            ->paginate(20);

        return Inertia::render('DMS/Documents', [
            'documents' => $documents,
            'categories' => Category::active()->get(),
            'folders' => Folder::all(),
            'filters' => $filters,
        ]);
    }

    /**
     * Show document details.
     */
    public function show(Document $document)
    {
        $this->dmsService->logAccess($document, Auth::user(), 'viewed');

        $document->load(['category', 'folder', 'creator', 'shares.user', 'versionHistory.creator']);

        return Inertia::render('DMS/DocumentView', [
            'document' => $document,
            'versions' => $document->versionHistory,
        ]);
    }

    /**
     * Create new document form.
     */
    public function create()
    {
        return Inertia::render('DMS/DocumentCreate', [
            'categories' => Category::active()->get(),
            'folders' => Folder::all(),
        ]);
    }

    /**
     * Store a new document.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:dms_categories,id',
            'folder_id' => 'nullable|exists:dms_folders,id',
            'file' => 'required|file|max:102400', // 100MB max
            'visibility' => 'required|in:private,internal,public',
            'tags' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        $document = $this->dmsService->createDocument(
            $validated,
            $request->file('file'),
            Auth::user()
        );

        return redirect()->route('dms.documents.show', $document)
            ->with('success', 'Document created successfully.');
    }

    /**
     * Update a document.
     */
    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:dms_categories,id',
            'folder_id' => 'nullable|exists:dms_folders,id',
            'visibility' => 'required|in:private,internal,public',
            'tags' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        $this->dmsService->updateDocument($document, $validated, Auth::user());

        return back()->with('success', 'Document updated successfully.');
    }

    /**
     * Download a document.
     */
    public function download(Document $document): StreamedResponse
    {
        $this->dmsService->logAccess($document, Auth::user(), 'downloaded');

        return Storage::disk('public')->download(
            $document->file_path,
            $document->original_file_name ?? $document->title
        );
    }

    /**
     * Delete a document.
     */
    public function destroy(Document $document)
    {
        $this->dmsService->deleteDocument($document, Auth::user());

        return redirect()->route('dms.documents')
            ->with('success', 'Document deleted successfully.');
    }

    /**
     * Share a document with users.
     */
    public function share(Request $request, Document $document)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'permissions' => 'nullable|array',
        ]);

        $this->dmsService->shareDocument(
            $document,
            $validated['user_ids'],
            Auth::user(),
            $validated['permissions'] ?? []
        );

        return back()->with('success', 'Document shared successfully.');
    }

    /**
     * Get shared documents.
     */
    public function shared()
    {
        $user = Auth::user();
        $documents = Document::whereHas('shares', function ($query) use ($user) {
            $query->where('shared_with', $user->id)->active();
        })->with(['category', 'creator'])->paginate(20);

        return Inertia::render('DMS/SharedDocuments', [
            'documents' => $documents,
        ]);
    }

    /**
     * Get document analytics.
     */
    public function analytics()
    {
        $statistics = $this->dmsService->getStatistics(Auth::user());
        $recentActivity = $this->dmsService->getRecentActivity(50);

        return Inertia::render('DMS/Analytics', [
            'statistics' => $statistics,
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Get all categories.
     */
    public function categories()
    {
        return Inertia::render('DMS/Categories', [
            'categories' => Category::withCount('documents')->get(),
        ]);
    }

    /**
     * Store a new category.
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
        ]);

        $this->dmsService->createCategory($validated, Auth::user());

        return back()->with('success', 'Category created successfully.');
    }

    /**
     * Update a category.
     */
    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    /**
     * Delete a category.
     */
    public function destroyCategory(Category $category)
    {
        if ($category->documents()->count() > 0) {
            return back()->with('error', 'Cannot delete category with documents.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    /**
     * Get all folders.
     */
    public function folders()
    {
        return Inertia::render('DMS/Folders', [
            'folders' => Folder::withCount('documents')->with('children')->whereNull('parent_id')->get(),
        ]);
    }

    /**
     * Store a new folder.
     */
    public function storeFolder(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:dms_folders,id',
        ]);

        $this->dmsService->createFolder($validated, Auth::user());

        return back()->with('success', 'Folder created successfully.');
    }

    /**
     * Update a folder.
     */
    public function updateFolder(Request $request, Folder $folder)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:dms_folders,id',
        ]);

        $folder->update($validated);

        return back()->with('success', 'Folder updated successfully.');
    }

    /**
     * Delete a folder.
     */
    public function destroyFolder(Folder $folder)
    {
        if ($folder->documents()->count() > 0) {
            return back()->with('error', 'Cannot delete folder with documents.');
        }

        $folder->delete();

        return back()->with('success', 'Folder deleted successfully.');
    }

    /**
     * Access control page.
     */
    public function accessControl()
    {
        return Inertia::render('DMS/AccessControl', [
            'documents' => Document::with('shares')->get(),
        ]);
    }

    /**
     * Update access control settings.
     */
    public function updateAccessControl(Request $request)
    {
        // Implementation depends on requirements
        return back()->with('success', 'Access control updated.');
    }

    // =========================================================================
    // VERSION MANAGEMENT METHODS
    // =========================================================================

    /**
     * Get version history for a document.
     */
    public function versions(Document $document): JsonResponse
    {
        $versions = $this->dmsService->getVersionHistory($document);

        return response()->json([
            'versions' => $versions->map(function ($version) {
                return [
                    'id' => $version->id,
                    'version' => $version->version,
                    'change_summary' => $version->change_summary,
                    'file_size' => $version->file_size,
                    'checksum' => $version->checksum,
                    'created_at' => $version->created_at,
                    'creator' => $version->creator ? [
                        'id' => $version->creator->id,
                        'name' => $version->creator->name,
                    ] : null,
                ];
            }),
            'current_version' => $document->version,
        ]);
    }

    /**
     * Upload a new version of a document.
     */
    public function uploadVersion(Request $request, Document $document)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'change_summary' => 'nullable|string|max:500',
        ]);

        $version = $this->dmsService->uploadNewVersion(
            $document,
            $request->file('file'),
            Auth::user(),
            $validated['change_summary'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'New version uploaded successfully.',
            'version' => [
                'id' => $version->id,
                'version' => $version->version,
                'change_summary' => $version->change_summary,
                'file_size' => $version->file_size,
                'created_at' => $version->created_at,
            ],
        ]);
    }

    /**
     * Download a specific version of a document.
     */
    public function downloadVersion(Document $document, int $version): StreamedResponse|JsonResponse
    {
        $fileInfo = $this->dmsService->getVersionFile($document, $version);

        if (! $fileInfo) {
            return response()->json(['error' => 'Version not found'], 404);
        }

        $this->dmsService->logAccess($document, Auth::user(), 'version_downloaded', [
            'version' => $version,
        ]);

        return response()->download($fileInfo['path'], $fileInfo['name'], [
            'Content-Type' => $fileInfo['mime_type'],
        ]);
    }

    /**
     * Rollback to a specific version.
     */
    public function rollbackVersion(Document $document, int $version)
    {
        $versionRecord = DocumentVersion::where('document_id', $document->id)
            ->where('id', $version)
            ->firstOrFail();

        $this->dmsService->rollbackToVersion($document, $version, Auth::user());

        return response()->json([
            'success' => true,
            'message' => "Rolled back to version {$versionRecord->version} successfully.",
        ]);
    }

    /**
     * Compare two versions of a document.
     */
    public function compareVersions(Request $request, Document $document): JsonResponse
    {
        $validated = $request->validate([
            'version1' => 'required|integer',
            'version2' => 'required|integer',
        ]);

        $comparison = $this->dmsService->compareVersions(
            $document,
            $validated['version1'],
            $validated['version2']
        );

        return response()->json($comparison);
    }
}
