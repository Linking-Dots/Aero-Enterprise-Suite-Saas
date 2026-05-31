<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ApiUsageController extends Controller
{
    public function index(Request $request): Response
    {
        // Aggregate usage from api_request_logs if it exists, else return empty stats
        $hasUsageLogs = Schema::hasTable('api_request_logs');

        $stats = $hasUsageLogs ? [
            'total_requests_today' => DB::table('api_request_logs')->whereDate('created_at', today())->count(),
            'total_requests_week' => DB::table('api_request_logs')->where('created_at', '>=', now()->subDays(7))->count(),
            'top_endpoints' => DB::table('api_request_logs')
                ->selectRaw('endpoint, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('endpoint')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'requests_by_key' => DB::table('api_request_logs')
                ->join('api_keys', 'api_keys.id', '=', 'api_request_logs.api_key_id')
                ->selectRaw('api_keys.name, COUNT(*) as count')
                ->where('api_request_logs.created_at', '>=', now()->subDays(7))
                ->groupBy('api_keys.name')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
        ] : [
            'total_requests_today' => 0,
            'total_requests_week' => 0,
            'top_endpoints' => [],
            'requests_by_key' => [],
            'note' => 'Enable API request logging to see usage analytics.',
        ];

        return Inertia::render('Core/Api/Usage', ['stats' => $stats]);
    }
}
