<?php

namespace Aero\RealEstate\Models;

use Aero\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyPhoto extends TenantModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_property_photos';

    protected $fillable = [
        'property_id', 'filename', 'original_filename', 'file_path', 'file_size',
        'mime_type', 'width', 'height', 'alt_text', 'caption', 'photo_type',
        'room_type', 'is_primary', 'sort_order', 'created_by',
    ];

    protected $casts = [
        'property_id' => 'integer',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
        'created_by' => 'integer',
    ];

    const TYPE_EXTERIOR = 'exterior';

    const TYPE_INTERIOR = 'interior';

    const TYPE_AERIAL = 'aerial';

    const TYPE_FLOOR_PLAN = 'floor_plan';

    const TYPE_VIRTUAL_TOUR = 'virtual_tour';

    const ROOM_LIVING_ROOM = 'living_room';

    const ROOM_KITCHEN = 'kitchen';

    const ROOM_MASTER_BEDROOM = 'master_bedroom';

    const ROOM_BEDROOM = 'bedroom';

    const ROOM_BATHROOM = 'bathroom';

    const ROOM_DINING_ROOM = 'dining_room';

    const ROOM_GARAGE = 'garage';

    const ROOM_BASEMENT = 'basement';

    const ROOM_ATTIC = 'attic';

    const ROOM_YARD = 'yard';

    const ROOM_POOL = 'pool';

    const ROOM_OTHER = 'other';

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getUrlAttribute()
    {
        return asset('storage/'.$this->file_path);
    }

    public function getThumbnailUrlAttribute()
    {
        $pathInfo = pathinfo($this->file_path);
        $thumbnailPath = $pathInfo['dirname'].'/thumbnails/'.$pathInfo['filename'].'_thumb.'.$pathInfo['extension'];

        return asset('storage/'.$thumbnailPath);
    }

    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getAspectRatioAttribute()
    {
        if ($this->width && $this->height) {
            return round($this->width / $this->height, 2);
        }

        return null;
    }

    public function isLandscape()
    {
        return $this->width > $this->height;
    }

    public function isPortrait()
    {
        return $this->height > $this->width;
    }

    public function isSquare()
    {
        return $this->width === $this->height;
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('photo_type', $type);
    }

    public function scopeByRoom($query, $room)
    {
        return $query->where('room_type', $room);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('is_primary', 'desc')
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }
}
