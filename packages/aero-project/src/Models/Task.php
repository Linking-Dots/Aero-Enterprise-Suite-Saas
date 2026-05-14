<?php

namespace Aero\Project\Models;

use Aero\Contracts\Models\TenantModel;

use Illuminate\Database\Eloquent\Model;

class Task extends TenantModel
{
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'due_date',
        'is_complete',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
