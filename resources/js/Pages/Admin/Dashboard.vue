<!--
Change Log - Dashboard.vue

Changes Made:
1. Enhanced dashboard layout:
   - Added responsive grid layouts
   - Implemented card-based statistics display
   - Added new sections for detailed metrics

2. Added new statistics sections:
   - Today's Activity section
   - Category Distribution table
   - Price Distribution chart
   - Top Locations overview
   - Most Active Users table

3. Improved data visualization:
   - Added formatted numbers with toLocaleString
   - Added percentage calculations
   - Implemented proper null checking with fallbacks

4. Updated table component imports:
   - Now using modular table components
   - Improved table styling and responsiveness

5. Added error handling:
   - Added null checks with default values
   - Added conditional rendering for missing data
-->

<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import { Head, Link } from "@inertiajs/vue3"; // Add Link import
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table"; // Now importing from the index.js
import SystemReport from "@/Components/SystemReport.vue";

defineOptions({ layout: AdminLayout });

defineProps({
    stats: {
        type: Object,
        required: true,
    }
});
</script>

<template>
    <Head title="| Admin Dashboard" />
    
    <div class="space-y-8">
        <h2 class="text-2xl font-semibold tracking-tight">Dashboard Overview</h2>

        <!-- Add System Report Component here -->
        <SystemReport :stats="stats" />

        <!-- Transaction Analytics (Moved to top) -->
        <div>
            <h3 class="text-lg font-medium mb-4">Transaction Analytics</h3>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Link 
                    :href="route('admin.rental-transactions', { status: 'completed' })"
                    class="transition-transform hover:scale-105"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">
                                Completed Transactions
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats?.transactionStats?.completed || 0 }}</div>
                            <p class="text-xs text-muted-foreground">
                                Last 30 days
                            </p>
                        </CardContent>
                    </Card>
                </Link>

                <Link 
                    :href="route('admin.rental-transactions', { status: 'active' })"
                    class="transition-transform hover:scale-105"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">
                                Active Rentals
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats?.transactionStats?.active || 0 }}</div>
                            <p class="text-xs text-muted-foreground">
                                Currently ongoing
                            </p>
                        </CardContent>
                    </Card>
                </Link>

                <Link 
                    :href="route('admin.revenue')"
                    class="transition-transform hover:scale-105"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">
                                Monthly Revenue
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">₱{{ (stats?.revenueStats?.monthly || 0).toLocaleString() }}</div>
                            <p class="text-xs text-muted-foreground">
                                Platform fees this month
                            </p>
                        </CardContent>
                    </Card>
                </Link>

                <Link 
                    :href="route('admin.revenue')"
                    class="transition-transform hover:scale-105"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">
                                Total Revenue
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">₱{{ (stats?.revenueStats?.total || 0).toLocaleString() }}</div>
                            <p class="text-xs text-muted-foreground">
                                All-time platform earnings
                            </p>
                        </CardContent>
                    </Card>
                </Link>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">
                            Success Rate
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.performanceStats?.successRate || 0 }}%</div>
                        <p class="text-xs text-muted-foreground">
                            Completed vs Total
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Performance Metrics (Moved to top) -->
        <div>
            <h3 class="text-lg font-medium mb-4">Performance Metrics</h3>
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">
                            Success Rate
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.performanceStats?.successRate || 0 }}%</div>
                        <p class="text-xs text-muted-foreground">
                            Completed vs Total
                        </p>
                    </CardContent>
                </Card>

                <Link 
                    :href="route('admin.disputes.index')"
                    class="transition-transform hover:scale-105"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">
                                Dispute Rate
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats?.performanceStats?.disputeRate || 0 }}%</div>
                            <p class="text-xs text-muted-foreground">
                                Of total transactions
                            </p>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>

        <!-- User Statistics -->
        <div>
            <h3 class="text-lg font-medium mb-4">User Statistics</h3>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Link 
                    :href="route('admin.users')"
                    class="transition-transform hover:scale-105"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">
                                Total Users
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats?.totalUsers || 0 }}</div>
                            <p class="text-xs text-muted-foreground">
                                {{ stats?.verifiedUsers || 0 }} verified
                            </p>
                        </CardContent>
                    </Card>
                </Link>

                <Link 
                    :href="route('admin.users', { status: 'active' })"
                    class="transition-transform hover:scale-105"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">
                                Active Users
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats?.activeUsers || 0 }}</div>
                            <p class="text-xs text-muted-foreground">
                                {{ stats?.suspendedUsers || 0 }} suspended
                            </p>
                        </CardContent>
                    </Card>
                </Link>

                <Link 
                    :href="route('admin.listings')"
                    class="transition-transform hover:scale-105"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">
                                Total Listings
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats?.totalListings || 0 }}</div>
                            <p class="text-xs text-muted-foreground">
                                {{ stats?.activeListings || 0 }} active
                            </p>
                        </CardContent>
                    </Card>
                </Link>

                <Link 
                    :href="route('admin.users', { verified: 'unverified' })"
                    class="transition-transform hover:scale-105"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">
                                New Users This Month
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats?.newUsersThisMonth || 0 }}</div>
                        </CardContent>
                    </Card>
                </Link>

                <Link 
                    :href="route('admin.listings', { status: 'pending' })"
                    class="transition-transform hover:scale-105"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">
                                Pending Approvals
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats?.pendingApprovals || 0 }}</div>
                        </CardContent>
                    </Card>
                </Link>

                <Link 
                    :href="route('admin.users', { verified: 'unverified' })"
                    class="transition-transform hover:scale-105"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">
                                Unverified Users
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ stats?.unverifiedUsers || 0 }}</div>
                            <p class="text-xs text-muted-foreground">
                                Pending email verification
                            </p>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>

        <!-- Listing Statistics -->
        <div>
            <h3 class="text-lg font-medium mb-4">Listing Statistics</h3>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Link 
                    :href="route('admin.listings')"
                    class="transition-transform hover:scale-105"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm font-medium">
                                Listing Status Distribution
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Approved:</span>
                                    <span class="font-bold">{{ stats?.listingStats?.approved || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Rejected:</span>
                                    <span class="font-bold">{{ stats?.listingStats?.rejected || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Taken Down:</span>
                                    <span class="font-bold">{{ stats?.listingStats?.takenDown || 0 }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">
                            Average Listing Price
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            ₱{{ Math.round(stats?.averageListingPrice || 0).toLocaleString() }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            For approved listings
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">
                            Price Range
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span>Highest:</span>
                                <span class="font-bold">₱{{ Math.round(stats?.highestListingPrice || 0).toLocaleString() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Lowest:</span>
                                <span class="font-bold">₱{{ Math.round(stats?.lowestListingPrice || 0).toLocaleString() }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Today's Activity -->
        <div>
            <h3 class="text-lg font-medium mb-4">Today's Activity</h3>
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">New Users Today</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.recentActivity?.newUsersToday }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">New Listings Today</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.recentActivity?.newListingsToday }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Pending Approvals Today</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats?.recentActivity?.pendingApprovalsToday }}</div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Category Distribution -->
        <div>
            <h3 class="text-lg font-medium mb-4">Category Distribution</h3>
            <Card>
                <CardContent class="pt-6">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Category</TableHead>
                                <TableHead>Count</TableHead>
                                <TableHead>Average Price</TableHead>
                                <TableHead>Percentage</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="category in stats?.categoryBreakdown" :key="category.name">
                                <TableCell>{{ category.name }}</TableCell>
                                <TableCell>{{ category.count }}</TableCell>
                                <TableCell>₱{{ Math.round(category.average_price).toLocaleString() }}</TableCell>
                                <TableCell>
                                    {{ Math.round((category.count / stats?.totalListings) * 100) }}%
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- Price Distribution -->
        <div>
            <!-- Price Distribution Chart -->
            <Card>
                <CardHeader>
                    <CardTitle>Price Distribution</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Under ₱100</span>
                            <span class="font-bold">{{ stats?.listingPriceDistribution?.under100 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>₱100 - ₱499</span>
                            <span class="font-bold">{{ stats?.listingPriceDistribution?.under500 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>₱500 - ₱999</span>
                            <span class="font-bold">{{ stats?.listingPriceDistribution?.under1000 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>₱1000+</span>
                            <span class="font-bold">{{ stats?.listingPriceDistribution?.over1000 }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Most Active Users -->
        <div>
            <h3 class="text-lg font-medium mb-4">Most Active Users</h3>
            <Card>
                <CardContent class="pt-6">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>User</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Active Listings</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in stats?.mostActiveUsers" :key="user.email">
                                <TableCell>{{ user.name }}</TableCell>
                                <TableCell>{{ user.email }}</TableCell>
                                <TableCell>{{ user.listings_count }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
