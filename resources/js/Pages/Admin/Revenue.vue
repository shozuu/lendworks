<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { formatDate } from "@/lib/formatters";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { Link } from "@inertiajs/vue3";  // Add Link import if not already present
import { Button } from "@/components/ui/button";
import { Download, Printer, Image } from "lucide-vue-next"; // Add Printer and Image icons
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import RevenueChart from "@/Components/RevenueChart.vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    revenue: Object,
    transactions: Object,
    trends: Object,
    filters: {
        type: Object,
        default: () => ({
            dateRange: 'last30',
            sort: 'latest',
            graphStartDate: '',
            graphEndDate: ''
        })
    }
});

const dateRange = ref(props.filters.dateRange);
const sort = ref(props.filters.sort);
const graphStartDate = ref(props.filters.graphStartDate || '');
const graphEndDate = ref(props.filters.graphEndDate || '');

// Update handlers
const updateFilters = (newFilters) => {
    router.get(
        route('admin.revenue'),
        { 
            ...props.filters,
            ...newFilters
        },
        { preserveState: true }
    );
};

// Watch for table filter changes
watch([dateRange, sort], ([newDateRange, newSort]) => {
    updateFilters({ 
        dateRange: newDateRange, 
        sort: newSort,
        // Preserve graph dates
        graphStartDate: graphStartDate.value,
        graphEndDate: graphEndDate.value
    });
});

// Watch for graph date changes
watch([graphStartDate, graphEndDate], ([newStart, newEnd]) => {
    updateFilters({ 
        graphStartDate: newStart, 
        graphEndDate: newEnd,
        // Keep current table filters
        dateRange: dateRange.value,
        sort: sort.value
    });
});

// Reset graph dates
const resetGraphDates = () => {
    graphStartDate.value = '';
    graphEndDate.value = '';
    updateFilters({ 
        graphStartDate: '', 
        graphEndDate: '',
        dateRange: dateRange.value,
        sort: sort.value
    });
};

const exportToCSV = async () => {
    try {
        // Fetch all revenue data from the export endpoint
        const response = await fetch(route('admin.revenue.export'), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) throw new Error('Failed to fetch data');
        const allTransactions = await response.json();

        // Define headers
        const headers = [
            'Date',
            'Listing',
            'Renter',
            'Rental Amount',
            'Platform Fee',
            'Total Earnings',
            'Status'
        ].join(',');

        // Transform all transactions data into CSV rows
        const rows = allTransactions.map(transaction => [
            transaction.date,
            `"${transaction.listing.replace(/"/g, '""')}"`,
            `"${transaction.renter.replace(/"/g, '""')}"`,
            transaction.rental_amount,
            transaction.platform_fee,
            transaction.total_earnings,
            transaction.status
        ].join(','));

        // Create and download CSV
        const csv = [headers, ...rows].join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `revenue-${formatDate(new Date())}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

    } catch (error) {
        console.error('Export failed:', error);
    }
};

// Add ref for chart component
const chartRef = ref(null);

// Add chart export methods
const downloadChart = () => {
    chartRef.value?.downloadChart('png');
};

const printChart = () => {
    chartRef.value?.downloadChart('print');
};

// Add helper function for date formatting
const getDateRangeDescription = () => {
    if (graphStartDate.value && graphEndDate.value) {
        return `Revenue from ${formatDate(graphStartDate.value)} to ${formatDate(graphEndDate.value)}`;
    }

    // Default descriptions based on dateRange
    const descriptions = {
        today: 'Revenue for Today',
        yesterday: 'Revenue for Yesterday',
        last7: 'Revenue for Last 7 Days',
        last30: 'Revenue for Last 30 Days',
        thisMonth: 'Revenue for This Month',
        lastMonth: 'Revenue for Last Month',
        last90: 'Revenue for Last 90 Days',
        thisYear: 'Revenue for This Year',
        lastYear: 'Revenue for Last Year',
        allTime: 'All Time Revenue'
    };

    return descriptions[dateRange.value] || 'Revenue Overview';
};

</script>

<template>
    <Head title="| Admin - Revenue" />

    <div class="space-y-6">
        <!-- Header Section -->
        <div class="flex flex-col gap-4">
            <div class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-2">
                <h2 class="text-2xl font-semibold tracking-tight">Revenue Overview</h2>
                <div class="flex flex-wrap gap-2">
                    <Badge variant="outline">Total Earnings: ₱{{ revenue.total.toLocaleString() }}</Badge>
                    <Badge variant="success">This Month: ₱{{ revenue.monthly.toLocaleString() }}</Badge>
                    <Badge variant="secondary">Today: ₱{{ revenue.today.toLocaleString() }}</Badge>
                </div>
            </div>

            <!-- Revenue Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Average Per Transaction</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">₱{{ revenue.average.toLocaleString() }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Last 7 Days</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">₱{{ revenue.lastWeek.toLocaleString() }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Last 30 Days</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">₱{{ revenue.lastMonth.toLocaleString() }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium">Last 90 Days</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">₱{{ revenue.lastQuarter.toLocaleString() }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Graph Date Range Controls -->
            <div class="flex items-center gap-4">
                <div class="grid gap-1.5">
                    <Label class="text-sm">Graph Start Date</Label>
                    <Input
                        type="date"
                        v-model="graphStartDate"
                        :max="graphEndDate || undefined"
                    />
                </div>
                <div class="grid gap-1.5">
                    <Label class="text-sm">Graph End Date</Label>
                    <Input
                        type="date"
                        v-model="graphEndDate"
                        :min="graphStartDate || undefined"
                    />
                </div>
                <Button 
                    variant="outline" 
                    class="mt-6"
                    @click="resetGraphDates"
                >
                    Reset Range
                </Button>
            </div>

            <!-- Revenue Trend Chart -->
            <Card>
                <CardHeader class="flex flex-col space-y-2">
                    <div class="flex flex-row items-center justify-between">
                        <CardTitle>Revenue Trends</CardTitle>
                        <div class="flex items-center gap-2">
                            <Button variant="outline" size="sm" @click="downloadChart" class="gap-2">
                                <Image class="h-4 w-4" />
                                Save as PNG
                            </Button>
                            <Button variant="outline" size="sm" @click="printChart" class="gap-2">
                                <Printer class="h-4 w-4" />
                                Print Chart
                            </Button>
                        </div>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ getDateRangeDescription() }}
                    </p>
                </CardHeader>
                <CardContent>
                    <RevenueChart 
                        ref="chartRef" 
                        :data="trends" 
                        :overview="getDateRangeDescription()"
                        :total-revenue="revenue.total"
                    />
                </CardContent>
            </Card>

            <!-- Filters -->
            <div class="flex flex-wrap gap-4">
                <Select v-model="dateRange">
                    <SelectTrigger class="w-[200px]">
                        <SelectValue placeholder="Select Date Range" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="today">Today</SelectItem>
                        <SelectItem value="yesterday">Yesterday</SelectItem>
                        <SelectItem value="last7">Last 7 days</SelectItem>
                        <SelectItem value="last30">Last 30 days</SelectItem>
                        <SelectItem value="thisMonth">This Month</SelectItem>
                        <SelectItem value="lastMonth">Last Month</SelectItem>
                        <SelectItem value="last90">Last 90 days</SelectItem>
                        <SelectItem value="thisYear">This Year</SelectItem>
                        <SelectItem value="lastYear">Last Year</SelectItem>
                        <SelectItem value="allTime">All Time</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="sort">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="Sort by" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="latest">Latest First</SelectItem>
                        <SelectItem value="oldest">Oldest First</SelectItem>
                        <SelectItem value="highest">Highest Amount</SelectItem>
                        <SelectItem value="lowest">Lowest Amount</SelectItem>
                    </SelectContent>
                </Select>

                <Button @click="exportToCSV" variant="outline" class="gap-2">
                    <Download class="h-4 w-4" />
                    Export CSV
                </Button>
            </div>

        </div>

        <!-- Transactions Table -->
        <Card>
            <CardHeader>
                <CardTitle>Revenue Transactions</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="relative w-full overflow-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[120px]">Date</TableHead>
                                <TableHead class="min-w-[200px]">Listing Title</TableHead>
                                <TableHead class="min-w-[150px]">Renter</TableHead>
                                <TableHead class="text-right">Rental Amount</TableHead>
                                <TableHead class="text-right">Platform Fee</TableHead>
                                <TableHead class="text-right">Total Earnings</TableHead>
                                <TableHead class="w-[100px]">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="transaction in transactions.data" 
                                :key="transaction.id"
                                class="cursor-pointer hover:bg-muted/50 transition-colors"
                                @click="router.visit(route('admin.rental-transactions.show', transaction.id))"
                            >
                                <TableCell>{{ formatDate(transaction.created_at) }}</TableCell>
                                <TableCell class="font-medium">{{ transaction.listing.title }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ transaction.renter.name }}</TableCell>
                                <TableCell class="text-right">₱{{ transaction.total_price.toLocaleString() }}</TableCell>
                                <TableCell class="text-right">₱{{ transaction.service_fee.toLocaleString() }}</TableCell>
                                <TableCell class="text-right font-medium">
                                    ₱{{ (transaction.service_fee * 2).toLocaleString() }}
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge>{{ transaction.status }}</Badge>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
        <PaginationLinks :paginator="transactions" />
    </div>
</template>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>
