<?php

namespace Aero\Core\Jobs;

use Aero\Core\Models\DataExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public DataExport $export
    ) {}

    public function handle(): void
    {
        $this->export->update(['status' => 'processing']);

        try {
            $filePath = $this->generateExportFile();
            
            $this->export->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'completed_at' => now(),
            ]);
        } catch (\Exception $e) {
            $this->export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    protected function generateExportFile(): string
    {
        $entityType = $this->export->entity_type;
        $format = $this->export->format;
        $filters = $this->export->filters;

        $modelClass = $this->getModelClass($entityType);
        $query = $modelClass::query();

        if (! empty($filters)) {
            foreach ($filters as $key => $value) {
                $query->where($key, $value);
            }
        }

        $records = $query->get();
        $this->export->update(['record_count' => $records->count()]);

        $filePath = $this->getFilePath($entityType, $format);

        if ($format === 'csv') {
            $this->generateCsv($records, $filePath);
        } elseif ($format === 'json') {
            $this->generateJson($records, $filePath);
        }

        return $filePath;
    }

    protected function getModelClass(string $entityType): string
    {
        $models = [
            'users' => \App\Models\User::class,
            'roles' => \Aero\HRMAC\Models\Role::class,
            'tags' => \Aero\Core\Models\Tag::class,
        ];

        return $models[$entityType] ?? throw new \InvalidArgumentException("Unknown entity type: {$entityType}");
    }

    protected function getFilePath(string $entityType, string $format): string
    {
        $tenantId = tenant('id') ?? 'central';
        $filename = "{$entityType}-{$tenantId}-" . now()->format('YmdHis') . ".{$format}";
        
        return "exports/{$tenantId}/{$filename}";
    }

    protected function generateCsv($records, string $filePath): void
    {
        $file = fopen(storage_path('app/' . $filePath), 'w');
        
        if ($records->isNotEmpty()) {
            $headers = array_keys($records->first()->toArray());
            fputcsv($file, $headers);

            foreach ($records as $record) {
                fputcsv($file, $record->toArray());
            }
        }

        fclose($file);
    }

    protected function generateJson($records, string $filePath): void
    {
        Storage::put($filePath, json_encode($records, JSON_PRETTY_PRINT));
    }
}
