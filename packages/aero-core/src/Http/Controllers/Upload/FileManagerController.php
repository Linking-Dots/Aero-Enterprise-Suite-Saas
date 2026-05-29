<?php

namespace Aero\Core\Http\Controllers\Upload;

use Aero\Core\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FileManagerController extends Controller
{
    /**
     * Disks the file manager is allowed to browse.
     *
     * Plan 02 T11 — closes the tenant filesystem leak. Phase 0 T5 already
     * enabled Stancl FilesystemTenancyBootstrapper which auto-tenant-prefixes
     * `local` and `public` paths. The remaining concern was that the
     * controller accepted `?disk=$ANYTHING` from the query string — an
     * attacker could pass `?disk=s3` (a non-tenant-aware disk) and read
     * across boundaries. Whitelisting closes that.
     *
     * Operators wanting to expose additional disks must add them here.
     */
    private const ALLOWED_DISKS = ['local', 'public'];

    /**
     * Resolve the disk to use from request input, defaulting to public
     * and rejecting unknown disks. Returns a Filesystem instance.
     */
    private function resolveDisk(Request $request): \Illuminate\Contracts\Filesystem\Filesystem
    {
        $disk = $request->get('disk', 'public');

        if (! in_array($disk, self::ALLOWED_DISKS, true)) {
            abort(422, "Disk '{$disk}' is not exposed to the file manager.");
        }

        return Storage::disk($disk);
    }

    /**
     * Display the file manager page.
     */
    public function index(): Response
    {
        return Inertia::render('Core/FileManager/Index', [
            'title' => 'File Manager',
        ]);
    }

    /**
     * Browse files in a directory.
     */
    public function browse(Request $request)
    {
        $path = $request->get('path', '');
        $perPage = $request->get('per_page', 50);

        try {
            $storage = $this->resolveDisk($request);

            // Get directories and files
            $directories = collect($storage->directories($path))
                ->map(fn ($dir) => [
                    'name' => basename($dir),
                    'path' => $dir,
                    'type' => 'directory',
                    'size' => null,
                    'last_modified' => null,
                ]);

            $files = collect($storage->files($path))
                ->map(fn ($file) => [
                    'name' => basename($file),
                    'path' => $file,
                    'type' => 'file',
                    'size' => $storage->size($file),
                    'last_modified' => $storage->lastModified($file),
                    'url' => $storage->url($file),
                    'mime_type' => $storage->mimeType($file),
                ]);

            $items = $directories->concat($files)->values();

            return response()->json([
                'data' => $items,
                'current_path' => $path,
                'parent_path' => dirname($path) !== '.' ? dirname($path) : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to browse directory',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a file.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
            'path' => 'nullable|string',
        ]);

        $path = $request->get('path', 'uploads');
        $disk = $request->get('disk', 'public');
        // Plan 02 T11 — fail fast on disallowed disks before file upload work
        $storage = $this->resolveDisk($request);

        try {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();

            // Ensure unique filename
            $fullPath = $path.'/'.$filename;

            if ($storage->exists($fullPath)) {
                $filename = pathinfo($filename, PATHINFO_FILENAME)
                    .'_'.time()
                    .'.'.$file->getClientOriginalExtension();
                $fullPath = $path.'/'.$filename;
            }

            $storedPath = $file->storeAs($path, $filename, $disk);

            return response()->json([
                'message' => 'File uploaded successfully',
                'file' => [
                    'name' => $filename,
                    'path' => $storedPath,
                    'url' => $storage->url($storedPath),
                    'size' => $storage->size($storedPath),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to upload file',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a file or directory.
     */
    public function destroy(Request $request, string $id)
    {
        $path = base64_decode($id);

        try {
            $storage = $this->resolveDisk($request);

            if ($storage->exists($path)) {
                // Check if it's a directory
                if (empty(pathinfo($path, PATHINFO_EXTENSION))) {
                    $storage->deleteDirectory($path);
                } else {
                    $storage->delete($path);
                }

                return response()->json([
                    'message' => 'File deleted successfully',
                ]);
            }

            return response()->json([
                'error' => 'File not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete file',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get storage statistics.
     */
    public function stats(Request $request)
    {
        try {
            $storage = $this->resolveDisk($request);

            // Count files and calculate total size
            $totalSize = 0;
            $fileCount = 0;
            $directoryCount = 0;

            $allFiles = $storage->allFiles();
            $allDirectories = $storage->allDirectories();

            foreach ($allFiles as $file) {
                try {
                    $totalSize += $storage->size($file);
                    $fileCount++;
                } catch (\Exception $e) {
                    // Skip files that can't be read
                }
            }

            $directoryCount = count($allDirectories);

            return response()->json([
                'total_size' => $totalSize,
                'total_size_formatted' => $this->formatBytes($totalSize),
                'file_count' => $fileCount,
                'directory_count' => $directoryCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get storage stats',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }
}
