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

/**
 * Change Log:
 * 1. Combined Platform Management functionality into SystemManagementController
 * 2. Added category management methods (store, update, delete)
 * 3. Modified index() to include categories data for the platform tab
 * 4. Added Str facade for slug generation
 * 5. Added validation for category operations
 * 6. Added safeguards against deleting categories with listings
 * 7. Added Category model relationship handling
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\Category;
use Illuminate\Support\Str;

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

        // Add categories data
        $categories = Category::withCount('listings')
            ->orderBy('name')
            ->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'listings_count' => $category->listings_count
            ]);

        return Inertia::render('Admin/SystemManagement', [
            'systemInfo' => $systemInfo,
            'categories' => $categories
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

    // Add category management methods
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name']
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name'])
        ]);

        return back()->with('success', 'Category created successfully');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,' . $category->id]
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name'])
        ]);

        return back()->with('success', 'Category updated successfully');
    }

    public function deleteCategory(Category $category)
    {
        if ($category->listings()->exists()) {
            return back()->with('error', 'Cannot delete category that has listings');
        }

        $category->delete();
        return back()->with('success', 'Category deleted successfully');
    }
}
