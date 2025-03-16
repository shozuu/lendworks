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

defineOptions({ layout: AdminLayout });

const props = defineProps({
    revenue: Object,
    transactions: Object,
    filters: {
        type: Object,
        default: () => ({
            period: '30',
            sort: 'latest'
        })
    }
});

const period = ref(props.filters.period);
const sort = ref(props.filters.sort);

watch([period, sort], ([newPeriod, newSort]) => {
    router.get(
        route('admin.revenue'),
        { period: newPeriod, sort: newSort },
        { preserveState: true }
    );
});
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

            <!-- Filters -->
            <div class="flex gap-4">
                <Select v-model="period">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="Select Period" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="7">Last 7 days</SelectItem>
                        <SelectItem value="30">Last 30 days</SelectItem>
                        <SelectItem value="90">Last 90 days</SelectItem>
                        <SelectItem value="365">Last 365 days</SelectItem>
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
            </div>
        </div>

        <!-- Transactions Table -->
        <Card>
            <CardHeader>
                <CardTitle>Revenue Transactions</CardTitle>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Transaction</TableHead>
                            <TableHead>Rental Amount</TableHead>
                            <TableHead>Platform Fee</TableHead>
                            <TableHead>Total Earnings</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="transaction in transactions.data" :key="transaction.id">
                            <TableCell>{{ formatDate(transaction.created_at) }}</TableCell>
                            <TableCell>
                                <div class="font-medium">{{ transaction.listing.title }}</div>
                                <div class="text-sm text-muted-foreground">
                                    Renter: {{ transaction.renter.name }}
                                </div>
                            </TableCell>
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
