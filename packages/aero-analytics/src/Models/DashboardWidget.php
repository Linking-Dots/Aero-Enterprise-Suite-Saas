<?php

namespace Aero\Analytics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends TenantModel
{
    use HasFactory;

    protected $table = 'analytics_dashboard_widgets';

    protected $fillable = [
        'dashboard_id', 'widget_id', 'position_x', 'position_y',
        'width', 'height', 'order_index', 'is_visible', 'widget_config',
    ];

    protected $casts = [
        'dashboard_id' => 'integer',
        'widget_id' => 'integer',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'order_index' => 'integer',
        'is_visible' => 'boolean',
        'widget_config' => 'json',
    ];

    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function widget()
    {
        return $this->belongsTo(Widget::class);
    }
}
