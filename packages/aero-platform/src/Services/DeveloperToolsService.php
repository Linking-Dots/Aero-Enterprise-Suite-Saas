<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;

class DeveloperToolsService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function getCacheStats(): array
    {
        $stores = array_keys(config('cache.stores', []));
        $stats = [];

        foreach ($stores as $store) {
            $stats[$store] = [
                'driver' => config("cache.stores.{$store}.driver"),
                'size' => $this->cacheSize($store),
            ];
        }

        return $stats;
    }

    private function cacheSize(string $store): ?int
    {
        try {
            $driver = config("cache.stores.{$store}.driver");

            if ($driver === 'redis') {
                $connection = config("cache.stores.{$store}.connection", 'default');
                $info = Redis::connection($connection)->info('memory');

                return (int) ($info['used_memory'] ?? 0);
            }

            if ($driver === 'file') {
                $path = config("cache.stores.{$store}.path", storage_path('framework/cache/data'));
                if (! File::isDirectory($path)) {
                    return 0;
                }
                $size = 0;
                foreach (File::allFiles($path) as $f) {
                    $size += $f->getSize();
                }

                return $size;
            }

            if ($driver === 'database') {
                return (int) DB::table(config("cache.stores.{$store}.table", 'cache'))->count();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    public function clearCache(string $store, int $actorId): void
    {
        if (! array_key_exists($store, config('cache.stores', []))) {
            abort(422, "Unknown cache store: {$store}");
        }

        Cache::store($store)->flush();

        $this->audit->log(
            event: 'CACHE_CLEARED',
            action: 'clear',
            subject: null,
            description: "Cache store '{$store}' cleared by actor {$actorId}",
        );
    }

    public function getQueueStats(): array
    {
        $jobs = 0;
        $failed = 0;
        $byQueue = collect();

        try {
            $jobs = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            $byQueue = DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as count'))
                ->groupBy('queue')
                ->get();
        } catch (\Throwable) {
            // Table may not exist in all environments
        }

        return [
            'pending_total' => $jobs,
            'failed_total' => $failed,
            'by_queue' => $byQueue,
        ];
    }

    public function getQueueJobs(?string $queue, string $status = 'pending', int $perPage = 25): mixed
    {
        try {
            if ($status === 'failed') {
                $q = DB::table('failed_jobs');
                if ($queue) {
                    $q->where('queue', $queue);
                }

                return $q->orderByDesc('failed_at')->paginate($perPage);
            }

            $q = DB::table('jobs');
            if ($queue) {
                $q->where('queue', $queue);
            }

            return $q->orderByDesc('id')->paginate($perPage);
        } catch (\Throwable) {
            return [];
        }
    }

    public function retryJob(string $uuid, int $actorId): void
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);

        $this->audit->log(
            event: 'QUEUE_JOB_RETRIED',
            action: 'manage',
            subject: null,
            description: "Failed job {$uuid} retried by actor {$actorId}",
        );
    }

    public function deleteJob(string $uuid, int $actorId): void
    {
        Artisan::call('queue:forget', ['id' => $uuid]);

        $this->audit->log(
            event: 'QUEUE_JOB_DELETED',
            action: 'manage',
            subject: null,
            description: "Failed job {$uuid} forgotten by actor {$actorId}",
        );
    }

    public function getLogFiles(): array
    {
        $path = storage_path('logs');
        if (! File::isDirectory($path)) {
            return [];
        }

        $files = [];
        foreach (File::files($path) as $f) {
            $files[] = [
                'name' => $f->getFilename(),
                'size' => $f->getSize(),
                'modified_at' => date('c', $f->getMTime()),
            ];
        }

        usort($files, fn ($a, $b) => strcmp($b['modified_at'], $a['modified_at']));

        return $files;
    }

    public function downloadLog(string $filename, int $actorId): string
    {
        $safe = basename($filename);
        $path = storage_path('logs/'.$safe);

        if (! File::exists($path)) {
            abort(404, 'Log file not found');
        }

        $this->audit->log(
            event: 'LOG_FILE_DOWNLOADED',
            action: 'download',
            subject: null,
            description: "Log file '{$safe}' downloaded by actor {$actorId}",
        );

        return $path;
    }

    public function tailLog(string $filename, int $lines = 100): array
    {
        $safe = basename($filename);
        $path = storage_path('logs/'.$safe);

        if (! File::exists($path)) {
            return [];
        }

        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $last = $file->key();
        $start = max(0, $last - $lines);

        $out = [];
        $file->seek($start);
        while (! $file->eof()) {
            $line = $file->fgets();
            if ($line !== false && $line !== '') {
                $out[] = rtrim($line);
            }
        }

        return $out;
    }
}
