<?php

/**
 * SystemManagementController
 * Created: [Current Date]
 * 
 * Purpose:
 * Handles all system-level management operations including:
 * - System information display
 * - Cache management
 * - Maintenance mode control
 * - System optimization
 * 
 * Dependencies:
 * - Requires admin authentication
 * - Uses Artisan commands
 * - Interacts with cache and database
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SystemManagementController extends Controller
{
    public function index()
    {
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database' => [
                'name' => config('database.connections.mysql.database'),
                'version' => DB::select('SELECT VERSION() as version')[0]->version,
            ],
            'cache' => [
                'driver' => config('cache.default'),
                'status' => Cache::get('cache_test_key', 'Not Connected'),
            ],
            'storage' => [
                'uploads_dir' => is_writable(storage_path('app/public')),
                'logs_dir' => is_writable(storage_path('logs')),
            ],
            'maintenance_mode' => app()->isDownForMaintenance(),
        ];

        return Inertia::render('Admin/SystemManagement', [
            'systemInfo' => $systemInfo
        ]);
    }

    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            return back()->with('success', 'Cache cleared successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }

    public function toggleMaintenance(Request $request)
    {
        try {
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');
                $message = 'Application is now live';
            } else {
                Artisan::call('down', [
                    '--secret' => 'admin-' . time(),
                ]);
                $message = 'Application is now in maintenance mode';
            }
            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to toggle maintenance mode: ' . $e->getMessage());
        }
    }

    public function optimizeSystem()
    {
        try {
            Artisan::call('optimize');
            return back()->with('success', 'System optimized successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to optimize system: ' . $e->getMessage());
        }
    }
}
