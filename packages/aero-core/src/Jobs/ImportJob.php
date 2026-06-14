<?php

namespace Aero\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $entityType,
        public string $filePath,
        public string $format,
        public array $options = []
    ) {}

    public function handle(): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            if ($this->format === 'csv') {
                $results = $this->importFromCsv();
                $imported = $results['imported'];
                $skipped = $results['skipped'];
                $errors = $results['errors'];
            } elseif ($this->format === 'json') {
                $results = $this->importFromJson();
                $imported = $results['imported'];
                $skipped = $results['skipped'];
                $errors = $results['errors'];
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $errors[] = $e->getMessage();
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    protected function importFromCsv(): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $handle = fopen(storage_path('app/' . $this->filePath), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Empty CSV file']];
        }

        while (($row = fgetcsv($handle)) !== false) {
            try {
                $this->importRow($row, $header);
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = $e->getMessage();
            }
        }

        fclose($handle);

        return compact('imported', 'skipped', 'errors');
    }

    protected function importFromJson(): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $data = json_decode(file_get_contents(storage_path('app/' . $this->filePath)), true);

        if (! is_array($data)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Invalid JSON format']];
        }

        foreach ($data as $row) {
            try {
                $this->importRow($row, array_keys($row));
                $imported++;
            } catch (\Exception $e) {
                $skipped++;
                $errors[] = $e->getMessage();
            }
        }

        return compact('imported', 'skipped', 'errors');
    }

    protected function importRow(array $row, array $header): void
    {
        $modelClass = $this->getModelClass($this->entityType);
        $data = array_combine($header, $row);

        $model = $modelClass::create($data);

        if (method_exists($model, 'afterImport')) {
            $model->afterImport($data);
        }
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
}
