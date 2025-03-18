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
import { Download } from "lucide-vue-next";
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

const exportToCSV = () => {
    // Create CSV headers
    const headers = [
        'Date',
        'Listing Title',
        'Renter',
        'Rental Amount',
        'Platform Fee',
        'Total Earnings',
        'Status'
    ].join(',');

    // Convert transactions to CSV rows - Fix: Use props.transactions instead of transactions
    const rows = props.transactions.data.map(transaction => [
        formatDate(transaction.created_at),
        `"${transaction.listing.title}"`, // Quote titles to handle commas
        `"${transaction.renter.name}"`,
        transaction.total_price,
        transaction.service_fee,
        transaction.service_fee * 2,
        transaction.status
    ].join(','));

    // Combine headers and rows
    const csv = [headers, ...rows].join('\n');

    // Create download link
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `revenue-transactions-${formatDate(new Date())}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
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
                <CardHeader>
                    <CardTitle>Revenue Trends</CardTitle>
                </CardHeader>
                <CardContent>
                    <RevenueChart :data="trends" />
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
            <CardContent>
                <CardTitle>Revenue Transactions</CardTitle>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Listing Title</TableHead>
                            <TableHead>Renter</TableHead>
                            <TableHead>Rental Amount</TableHead>
                            <TableHead>Platform Fee</TableHead>
                            <TableHead>Total Earnings</TableHead>
                            <TableHead>Status</TableHead>
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
                            <TableCell>₱{{ transaction.total_price.toLocaleString() }}</TableCell>
                            <TableCell>₱{{ transaction.service_fee.toLocaleString() }}</TableCell>
                            <TableCell class="font-medium">
                                ₱{{ (transaction.service_fee * 2).toLocaleString() }}
                            </TableCell>
                            <TableCell>
                                <Badge>{{ transaction.status }}</Badge>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
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
