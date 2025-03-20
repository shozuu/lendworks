<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Link, router } from "@inertiajs/vue3";
import { formatDate, formatDateTime } from "@/lib/formatters";
import { Button } from "@/components/ui/button";
import { Download } from "lucide-vue-next";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import debounce from "lodash/debounce";
import { ref, watch } from "vue";
import { Separator } from "@/components/ui/separator";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    disputes: Object,
    stats: Object,
    filters: {
        type: Object,
        default: () => ({
            search: '',
            status: 'all',
            period: '30'
        })
    }
});

// Add filter state
const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? 'all');
const period = ref(props.filters?.period ?? '30');

// Status options
const statusOptions = [
    { value: 'all', label: 'All Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'reviewed', label: 'Under Review' },
    { value: 'resolved', label: 'Resolved' }
];

// Time period options
const periodOptions = [
    { value: '7', label: 'Last 7 days' },
    { value: '30', label: 'Last 30 days' },
    { value: '90', label: 'Last 90 days' },
    { value: 'all', label: 'All time' }
];

// Update filters
const updateFilters = (newFilters) => {
    router.get(
        route('admin.disputes.index'), // Change this line from 'admin.disputes' to 'admin.disputes.index'
        { ...props.filters, ...newFilters },
        { 
            preserveState: true,
            preserveScroll: true,
            replace: true
        }
    );
};

// Debounced search
const updateSearch = debounce((value) => {
    updateFilters({ search: value, page: 1 });
}, 300);

// Watch for changes
watch(search, updateSearch);
watch(status, (newVal) => updateFilters({ status: newVal, page: 1 }));
watch(period, (newVal) => updateFilters({ period: newVal, page: 1 }));

const exportToCSV = () => {
    const headers = [
        'Date',
        'Rental ID',
        'Reason',
        'Description',
        'Status'
    ].join(',');

    const rows = props.disputes.data.map(dispute => [
        formatDate(dispute.created_at),
        dispute.rental.id,
        `"${dispute.reason}"`,
        `"${dispute.description}"`,
        dispute.status
    ].join(','));

    const csv = [headers, ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `disputes-${formatDate(new Date())}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
};
</script>

<template>
    <Head title="Disputes | Admin" />

    <div class="space-y-6">
        <!-- Header with export button -->
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-semibold tracking-tight">Disputes</h2>
            <Button @click="exportToCSV" variant="outline" class="gap-2">
                <Download class="h-4 w-4" />
                Export CSV
            </Button>
        </div>

        <!-- Dispute Overview Card -->
        <Card class="mb-6">
            <CardHeader>
                <CardTitle>Dispute Overview</CardTitle>
                <CardDescription>
                    Dispute rate and resolution metrics
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex items-center gap-4">
                    <div>
                        <div class="text-3xl font-bold mb-1">{{ stats.disputeRate }}%</div>
                        <p class="text-sm text-muted-foreground">
                            Dispute Rate
                        </p>
                    </div>
                    <Separator orientation="vertical" className="h-12" />
                    <div>
                        <div class="text-3xl font-bold mb-1">
                            {{ stats.totalDisputes }} / {{ stats.totalTransactions }}
                        </div>
                        <p class="text-sm text-muted-foreground">
                            Transactions with Disputes
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Stats cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium">Total Disputes</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total }}</div>
                </CardContent>
            </Card>
            <!-- Add more stat cards similar to other admin pages -->
        </div>

        <!-- Add Filters Section -->
        <div class="flex flex-col sm:flex-row gap-4">
            <!-- Search Input -->
            <div class="flex-1">
                <Input 
                    type="search" 
                    placeholder="Search disputes..." 
                    v-model="search"
                />
            </div>

            <!-- Status Filter -->
            <Select v-model="status">
                <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="Filter by status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem 
                        v-for="option in statusOptions" 
                        :key="option.value" 
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <!-- Time Period Filter -->
            <Select v-model="period">
                <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="Time period" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem 
                        v-for="option in periodOptions" 
                        :key="option.value" 
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- Disputes Card -->
        <Card>
            <CardHeader>
                <CardTitle>Recent Disputes</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-8">
                    <Link
                        v-for="dispute in disputes.data" 
                        :key="dispute.id"
                        :href="route('admin.disputes.show', dispute.id)"
                        class="flex gap-4 p-4 border rounded-lg hover:bg-muted transition-colors"
                    >
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">Rental #{{ dispute.rental.id }}</span>
                                <span :class="{
                                    'text-muted-foreground': dispute.status === 'pending',
                                    'text-primary': dispute.status === 'reviewed',
                                    'text-destructive': dispute.status === 'resolved'
                                }">
                                    {{ dispute.status }}
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                Reason: {{ dispute.reason }}
                            </p>
                            <p class="text-sm">{{ dispute.description }}</p>
                        </div>
                        <div class="text-sm text-muted-foreground">
                            {{ formatDateTime(dispute.created_at) }}
                        </div>
                    </Link>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
