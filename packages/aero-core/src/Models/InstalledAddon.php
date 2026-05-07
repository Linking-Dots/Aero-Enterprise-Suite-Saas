<?php

namespace Aero\Core\Models;

use Illuminate\Database\Eloquent\Model;

class InstalledAddon extends Model
{
    protected $fillable = [
        'module_code', 'product_code', 'name', 'version',
        'license_key', 'install_path', 'status',
        'installed_at', 'last_checked_at', 'metadata',
    ];

    protected $casts = [
        'installed_at'    => 'datetime',
        'last_checked_at' => 'datetime',
        'metadata'        => 'array',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function installFullPath(): string
    {
        return base_path($this->install_path);
    }
}
