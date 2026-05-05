<?php

namespace Aero\Core\Traits;

trait Exportable
{
    /**
     * Get the fields that can be exported for this model.
     */
    public function getExportableFields(): array
    {
        return $this->fillable;
    }

    /**
     * Get the relations that should be included in exports.
     */
    public function getExportableRelations(): array
    {
        return [];
    }

    /**
     * Get the export headers for this model.
     */
    public function getExportHeaders(): array
    {
        return $this->getExportableFields();
    }

    /**
     * Get the export data for this model.
     */
    public function toExportArray(): array
    {
        $data = [];
        foreach ($this->getExportableFields() as $field) {
            $data[$field] = $this->$field;
        }

        foreach ($this->getExportableRelations() as $relation) {
            if ($this->$relation) {
                $data[$relation] = $this->$relation->toArray();
            }
        }

        return $data;
    }

    /**
     * Get the entity type identifier for this model.
     */
    public function getExportEntityType(): string
    {
        return strtolower(class_basename(static::class));
    }
}
