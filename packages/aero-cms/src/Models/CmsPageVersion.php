<?php

declare(strict_types=1);

namespace Aero\Cms\Models;

use Aero\Contracts\Models\TenantModel;

use Aero\Cms\Database\Factories\CmsPageVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPageVersion extends TenantModel
{
    use HasFactory;

    /**
     * Page versions are stored in the central (landlord) database.
     */
    protected $connection = 'central';

    protected $table = 'cms_page_versions';

    /**
     * This model only tracks created_at, not updated_at.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'page_id',
        'version_number',
        'blocks',
        'settings',
        'change_summary',
        'created_by',
    ];

    protected $casts = [
        'blocks' => 'array',
        'settings' => 'array',
        'version_number' => 'integer',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CmsPageVersionFactory
    {
        return CmsPageVersionFactory::new();
    }

    /**
     * Get the page this version belongs to.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }

    /**
     * Restore this version to the page.
     */
    public function restore(): void
    {
        $page = $this->page;

        // Delete current blocks
        $page->blocks()->delete();

        // Restore blocks from this version
        foreach ($this->blocks as $blockData) {
            $page->blocks()->create($blockData);
        }

        // Restore page settings
        if ($this->settings) {
            $page->update([
                'meta_title' => $this->settings['meta_title'] ?? $page->meta_title,
                'meta_description' => $this->settings['meta_description'] ?? $page->meta_description,
                'layout' => $this->settings['layout'] ?? $page->layout,
                'settings' => $this->settings['settings'] ?? $page->settings,
            ]);
        }
    }

    /**
     * Get the differences between this version and another.
     */
    public function diffWith(CmsPageVersion $other): array
    {
        return [
            'blocks_added' => count($this->blocks) - count($other->blocks),
            'settings_changed' => $this->settings !== $other->settings,
        ];
    }
}
