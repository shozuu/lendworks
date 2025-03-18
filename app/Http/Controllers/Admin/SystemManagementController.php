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
use App\Models\RentalTimelineEvent;

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

    public function getLogs(Request $request)
    {
        try {
            // Build base query with period filter
            $baseQuery = RentalTimelineEvent::query();
            if ($period = $request->input('period')) {
                if ($period !== 'all') {
                    $baseQuery->where('created_at', '>=', now()->subDays((int)$period));
                }
            }

            // Calculate stats using scopes
            $stats = [
                'system' => (clone $baseQuery)->ofType('system')->count(),
                'user' => (clone $baseQuery)->ofType('user')->count(),
                'admin' => (clone $baseQuery)->ofType('admin')->count(),
                'error' => (clone $baseQuery)->ofType('error')->count()
            ];

            // Build main query for paginated results
            $query = RentalTimelineEvent::query()
                ->with(['actor', 'rental_request']);

            // Apply type filter
            if ($type = $request->input('type', 'all')) {
                if ($type !== 'all') {
                    $query->ofType($type);
                }
            }

            // Apply period filter
            if ($period) {
                if ($period !== 'all') {
                    $query->where('created_at', '>=', now()->subDays((int)$period));
                }
            }

            // Apply search
            if ($search = $request->input('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('event_type', 'like', "%{$search}%")
                      ->orWhere('metadata', 'like', "%{$search}%")
                      ->orWhereHas('actor', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Get paginated logs
            $logs = $query->latest()
                ->paginate(50)
                ->through(function($log) {
                    return [
                        'id' => $log->id,
                        'created_at' => $log->created_at,
                        'event_type' => $log->event_type,
                        'description' => $this->formatLogDescription($log),
                        'actor' => $log->actor?->name,
                        'level' => $this->getLogLevel($log->event_type),
                        'log_type' => $this->getLogType($log->event_type),
                        'properties' => $log->metadata
                    ];
                });

            return Inertia::render('Admin/Logs', [
                'logs' => $logs,
                'stats' => $stats,
                'filters' => $request->only(['type', 'period', 'search'])
            ]);

        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Error loading system logs: ' . $e->getMessage());
        }
    }

    private function getEventTypesByCategory($category)
    {
        return match($category) {
            'user' => [
                'rental_created',
                'payment_submitted',
                'handover_completed',
                'return_initiated',
                'return_submitted'
            ],
            'admin' => [
                'payment_verified',
                'payment_rejected',
                'dispute_resolved',
                'rental_completed',
                'deposit_refunded',
                'lender_paid'
            ],
            'error' => [
                'payment_failed',
                'verification_failed',
                'system_error'
            ],
            'system' => [
                'status_updated',
                'rental_approved',
                'rental_rejected',
                'dispute_raised',
                'rental_cancelled'
            ],
            default => []
        };
    }

    private function getEventCountByCategory($category, $period)
    {
        $query = DB::table('rental_timeline_events')
            ->whereIn('event_type', $this->getEventTypesByCategory($category));

        if ($period && $period !== 'all') {
            $query->where('created_at', '>=', now()->subDays((int)$period));
        }

        return $query->count();
    }

    private function formatLogDescription($log)
    {
        $rentalId = $log->rental_id ? "#{$log->rental_id}" : "";
        
        return match($log->event_type) {
            'rental_created' => "New rental request created for Rental {$rentalId}",
            'payment_submitted' => "Payment submitted for Rental {$rentalId}",
            'payment_verified' => "Payment verified for Rental {$rentalId}",
            'payment_rejected' => "Payment rejected for Rental {$rentalId}",
            'handover_completed' => "Item handover completed for Rental {$rentalId}",
            'return_initiated' => "Return initiated for Rental {$rentalId}",
            'return_submitted' => "Return proof submitted for Rental {$rentalId}",
            'dispute_raised' => "Dispute raised for Rental {$rentalId}",
            'dispute_resolved' => "Dispute resolved for Rental {$rentalId}",
            'rental_completed' => "Rental {$rentalId} marked as completed",
            'deposit_refunded' => "Security deposit refunded for Rental {$rentalId}",
            'lender_paid' => "Lender payment processed for Rental {$rentalId}",
            default => str_replace('_', ' ', ucwords($log->event_type)) . ($rentalId ? " for Rental {$rentalId}" : "")
        };
    }

    private function getLogLevel($eventType)
    {
        return match(true) {
            in_array($eventType, ['payment_failed', 'verification_failed', 'system_error']) => 'error',
            in_array($eventType, ['dispute_raised', 'payment_rejected']) => 'warning',
            default => 'info'
        };
    }

    private function getLogType($eventType)
    {
        return match(true) {
            in_array($eventType, $this->getEventTypesByCategory('admin')) => 'admin',
            in_array($eventType, $this->getEventTypesByCategory('user')) => 'user',
            in_array($eventType, $this->getEventTypesByCategory('error')) => 'error',
            default => 'system'
        };
    }

    public function exportLogs(Request $request)
    {
        try {
            $query = DB::table('rental_timeline_events as e')
                ->leftJoin('users as u', 'e.actor_id', '=', 'u.id')
                ->leftJoin('rental_requests as r', 'e.rental_request_id', '=', 'r.id')
                ->select([
                    'e.created_at',
                    'e.event_type',
                    'u.name as actor_name',
                    'e.metadata',
                    'r.id as rental_id',
                    'e.status'
                ]);

            // Apply filters if they exist
            if ($type = $request->input('type', 'all')) {
                $query->when($type !== 'all', function($q) use ($type) {
                    switch ($type) {
                        case 'user':
                            return $q->whereIn('e.event_type', [
                                'rental_created',
                                'payment_submitted',
                                'handover_completed',
                                'return_initiated',
                                'return_submitted'
                            ]);
                        case 'admin':
                            return $q->whereIn('e.event_type', [
                                'payment_verified',
                                'payment_rejected',
                                'dispute_resolved',
                                'rental_completed',
                                'deposit_refunded',
                                'lender_paid'
                            ]);
                        case 'error':
                            return $q->whereIn('e.event_type', [
                                'payment_failed',
                                'verification_failed',
                                'system_error'
                            ]);
                        case 'system':
                            return $q->whereIn('e.event_type', [
                                'status_updated',
                                'rental_approved',
                                'rental_rejected',
                                'dispute_raised',
                                'rental_cancelled'
                            ]);
                    }
                });
            }

            // Apply period filter
            if ($period = $request->input('period')) {
                if ($period !== 'all') {
                    $query->where('e.created_at', '>=', now()->subDays($period));
                }
            }

            $logs = $query->orderBy('e.created_at', 'desc')->get();

            // Prepare CSV data
            $csvData = [];
            
            // Add headers
            $csvData[] = [
                'Timestamp',
                'Event Type',
                'Actor',
                'Rental ID',
                'Status',
                'Details'
            ];

            // Format each log entry
            foreach ($logs as $log) {
                $metadata = json_decode($log->metadata, true);
                $details = [];

                // Format metadata for readable output
                if (is_array($metadata)) {
                    foreach ($metadata as $key => $value) {
                        if (!is_array($value)) {
                            $details[] = "$key: $value";
                        }
                    }
                }

                $csvData[] = [
                    $log->created_at,
                    str_replace('_', ' ', ucwords($log->event_type)),
                    $log->actor_name ?? 'System',
                    $log->rental_id ?? 'N/A',
                    ucfirst($log->status ?? 'N/A'),
                    implode('; ', $details)
                ];
            }

            // Create CSV content
            $output = '';
            foreach ($csvData as $row) {
                $output .= implode(',', array_map(function($field) {
                    $field = str_replace('"', '""', $field); // Escape quotes
                    return '"' . $field . '"';
                }, $row)) . "\n";
            }

            // Generate response
            return response($output, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=system_logs_' . date('Y-m-d_H-i-s') . '.csv',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ]);

        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to export logs: ' . $e->getMessage());
        }
    }
}
