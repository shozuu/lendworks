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
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

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
            'maintenance_mode' => cache('maintenance_mode', false),
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

    public function toggleMaintenance()
    {
        $maintenanceMode = cache('maintenance_mode', false);
        cache(['maintenance_mode' => !$maintenanceMode], 60*24*30); // Store for 30 days
        
        return back()->with('success', 
            !$maintenanceMode ? 'Maintenance mode enabled' : 'Maintenance mode disabled'
        );
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

    public function exportDatabase()
    {
        try {
            $filename = 'database_export_' . date('Y-m-d_H-i-s') . '.csv';
            $sections = [];

            // Users Section
            $sections['Users'] = DB::table('users')
                ->select(
                    'name',
                    'email',
                    'status',
                    'email_verified_at',
                    'created_at'
                )
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'Name' => $item->name,
                        'Email' => $item->email,
                        'Status' => $item->status,
                        'Verified' => !empty($item->email_verified_at) ? 'Yes' : 'No',
                        'Join Date' => $item->created_at
                    ];
                })->toArray();

            // Listings Section with complete information
            $sections['Listings'] = DB::table('listings')
                ->join('users', 'listings.user_id', '=', 'users.id')
                ->join('categories', 'listings.category_id', '=', 'categories.id')
                ->leftJoin('locations', 'listings.location_id', '=', 'locations.id')
                ->select(
                    'listings.title',
                    'categories.name as category',
                    'listings.price',
                    'listings.status',
                    'listings.desc',
                    'users.name as owner',
                    'listings.created_at',
                    'locations.city',
                    'listings.is_available'
                )
                ->orderBy('listings.created_at', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'Title' => $item->title,
                        'Description' => $item->desc,
                        'Category' => $item->category,
                        'Price' => '₱' . number_format($item->price, 2),
                        'Status' => $item->status,
                        'Location' => $item->city ?? 'Unknown',
                        'Owner' => $item->owner,
                        'Available' => $item->is_available ? 'Yes' : 'No',
                        'Created' => $item->created_at
                    ];
                })->toArray();

            // Rental Transactions Section with complete information
            $sections['Transactions'] = DB::table('rental_requests')
                ->join('users as renters', 'rental_requests.renter_id', '=', 'renters.id')
                ->join('listings', 'rental_requests.listing_id', '=', 'listings.id')
                ->join('users as owners', 'listings.user_id', '=', 'owners.id')
                ->select(
                    'rental_requests.id',
                    'rental_requests.created_at',
                    'listings.title',
                    'renters.name as renter',
                    'owners.name as owner',
                    'rental_requests.total_price',
                    'rental_requests.service_fee',
                    'rental_requests.start_date',
                    'rental_requests.end_date',
                    'rental_requests.status'
                )
                ->orderBy('rental_requests.created_at', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'ID' => $item->id,
                        'Date' => $item->created_at,
                        'Listing' => $item->title,
                        'Renter' => $item->renter,
                        'Owner' => $item->owner,
                        'Total Amount' => '₱' . number_format($item->total_price, 2),
                        'Service Fee' => '₱' . number_format($item->service_fee, 2),
                        'Start Date' => $item->start_date,
                        'End Date' => $item->end_date,
                        'Status' => $item->status
                    ];
                })->toArray();

            // Payment Records Section
            $sections['Payments'] = DB::table('payment_requests')
                ->join('rental_requests', 'payment_requests.rental_request_id', '=', 'rental_requests.id')
                ->join('users', 'rental_requests.renter_id', '=', 'users.id')
                ->select(
                    'payment_requests.created_at',
                    'payment_requests.reference_number',
                    'users.name as payer',
                    'rental_requests.total_price',
                    'payment_requests.status',
                    'payment_requests.verified_at'
                )
                ->orderBy('payment_requests.created_at', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'Date' => $item->created_at,
                        'Reference' => $item->reference_number,
                        'Payer' => $item->payer,
                        'Amount' => '₱' . number_format($item->total_price, 2),
                        'Status' => $item->status,
                        'Verified Date' => $item->verified_at
                    ];
                })->toArray();

            // Generate CSV with proper formatting
            $output = '';
            foreach ($sections as $sectionName => $data) {
                if (empty($data)) continue;
                
                $output .= "\n{$sectionName}\n";
                
                // Headers
                $output .= implode(',', array_keys($data[0])) . "\n";
                
                // Data rows with proper escaping
                foreach ($data as $row) {
                    $output .= implode(',', array_map(function($value) {
                        $value = is_null($value) ? '' : $value;
                        $value = str_replace('"', '""', $value);
                        return '"' . $value . '"';
                    }, $row)) . "\n";
                }
                
                $output .= "\n"; // Add space between sections
            }

            return response($output, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=' . $filename,
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
}
