<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Badge } from "@/components/ui/badge";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectLabel,
    SelectValue,
} from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import TransactionCard from "@/Components/TransactionCard.vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    transactions: Object,
    stats: Object,
    filters: {
        type: Object,
        default: () => ({
            search: '',
            period: '30',
            status: 'all'
        })
    }
});

const periodOptions = [
    { value: '7', label: 'Last 7 days' },
    { value: '30', label: 'Last 30 days' },
    { value: '90', label: 'Last 90 days' }
];

const statusOptions = [
    { value: 'all', label: 'All Status' },
    { value: 'pending', label: 'Pending Request' },
    { value: 'approved', label: 'Owner Approved' },
    { value: 'to_handover', label: 'Ready for Handover' },
    { value: 'active', label: 'Active Rental' },
    { value: 'pending_return', label: 'Return Pending' },
    { value: 'return_scheduled', label: 'Return Scheduled' },
    { value: 'pending_return_confirmation', label: 'Return Confirmation' },
    { value: 'completed', label: 'Completed' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'cancelled', label: 'Cancelled' }
];

const getStatusBadge = (status) => {
    const badges = {
        pending: { variant: "warning", label: "Pending Request" },
        approved: { variant: "success", label: "Owner Approved" },
        to_handover: { variant: "info", label: "Ready for Handover" },
        active: { variant: "success", label: "Active Rental" },
        pending_return: { variant: "warning", label: "Return Pending" },
        return_scheduled: { variant: "info", label: "Return Scheduled" },
        pending_return_confirmation: { variant: "warning", label: "Return Confirmation" },
        completed: { variant: "default", label: "Completed" },
        rejected: { variant: "destructive", label: "Rejected" },
        cancelled: { variant: "muted", label: "Cancelled" }
    };
    return badges[status] || { variant: "default", label: status };
};

const updateFilters = (newFilters) => {
    router.get(
        route("admin.rental-transactions"),
        { ...props.filters, ...newFilters },
        { preserveState: true, preserveScroll: true }
    );
};

let timeout;
const handleSearch = (event) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        updateFilters({ search: event.target.value });
    }, 300);
};
</script>

<template>
    <Head title="| Admin - Rental Transactions" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4">
            <!-- Header Section -->
            <div class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-2">
                <div class="space-y-1">
                    <h2 class="text-2xl font-semibold tracking-tight">Rental Transactions</h2>
                    <p class="text-muted-foreground text-sm">Monitor rental activity and disputes</p>
                </div>
                <!-- Status Counts -->
                <div class="flex flex-wrap gap-2">
                    <Badge variant="outline">Total: {{ stats.total }}</Badge>
                    <Badge variant="warning">Pending: {{ stats.pending }}</Badge>
                    <Badge variant="success">Approved: {{ stats.approved }}</Badge>
                    <Badge variant="info">To Handover: {{ stats.renter_paid }}</Badge>
                    <Badge variant="success">Active: {{ stats.active }}</Badge>
                    <Badge variant="warning">Return Pending: {{ stats.pending_return }}</Badge>
                    <Badge variant="info">Return Scheduled: {{ stats.return_scheduled }}</Badge>
                    <Badge variant="warning">Return Confirmation: {{ stats.pending_return_confirmation }}</Badge>
                    <Badge variant="default">Completed: {{ stats.completed }}</Badge>
                    <Badge variant="destructive">Rejected: {{ stats.rejected }}</Badge>
                    <Badge variant="muted">Cancelled: {{ stats.cancelled }}</Badge>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="flex flex-wrap items-center gap-4">
                <!-- Add Search Input -->
                <div class="grow-[2] min-w-[200px]">
                    <input
                        type="text"
                        placeholder="Search by listing title, lender, or renter..."
                        class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 w-full h-10 px-3 text-sm border rounded-md"
                        :value="filters.search"
                        @input="handleSearch"
                    />
                </div>

                <Select 
                    :model-value="filters.period" 
                    @update:model-value="period => updateFilters({ period })"
                >
                    <SelectTrigger class="w-[180px]">
                        <SelectValue :placeholder="periodOptions.find(o => o.value === filters.period)?.label || 'Select Period'" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectLabel class="p-1 text-center">Time Period</SelectLabel>
                        <Separator class="my-2" />
                        <SelectItem v-for="option in periodOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select 
                    :model-value="filters.status" 
                    @update:model-value="status => updateFilters({ status })"
                >
                    <SelectTrigger class="w-[180px]">
                        <SelectValue :placeholder="statusOptions.find(o => o.value === filters.status)?.label || 'Select Status'" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectLabel class="p-1 text-center">Filter Status</SelectLabel>
                        <Separator class="my-2" />
                        <SelectItem v-for="option in statusOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Transactions List -->
        <div v-if="transactions.data.length" class="space-y-4">
            <TransactionCard 
                v-for="transaction in transactions.data" 
                :key="transaction.id" 
                :transaction="transaction"
                :status-badge="getStatusBadge(transaction.status)"
            />
            <PaginationLinks :paginator="transactions" />
        </div>
        <div v-else class="text-muted-foreground py-10 text-center">
            No transactions found
        </div>
    </div>
</template>
