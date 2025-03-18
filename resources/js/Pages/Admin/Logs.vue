<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Download } from "lucide-vue-next";
import { ref, watch } from "vue";
import debounce from "lodash/debounce";
import { formatDateTime } from "@/lib/formatters";
import { usePage } from "@inertiajs/vue3";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    logs: Object,
    stats: Object,
    filters: {
        type: Object,
        default: () => ({
            type: 'system',
            period: '7',
            level: 'all',
            search: ''
        })
    }
});

const type = ref(props.filters.type);
const period = ref(props.filters.period);
const level = ref(props.filters.level);
const search = ref(props.filters.search);

const typeOptions = [
    { value: 'all', label: 'All Logs' },
    { value: 'system', label: 'System Logs' },
    { value: 'user', label: 'User Activity' },
    { value: 'admin', label: 'Admin Actions' },
    { value: 'error', label: 'Error Logs' }
];

const periodOptions = [
    { value: '1', label: 'Last 24 Hours' },
    { value: '7', label: 'Last 7 Days' },
    { value: '30', label: 'Last 30 Days' },
    { value: '90', label: 'Last 90 Days' },
    { value: 'all', label: 'All Time' }
];

const levelOptions = [
    { value: 'all', label: 'All Levels' },
    { value: 'info', label: 'Info' },
    { value: 'warning', label: 'Warning' },
    { value: 'error', label: 'Error' }
];

const updateFilters = (newFilters) => {
    router.get(
        route('admin.logs'),
        { ...props.filters, ...newFilters },
        { preserveState: true }
    );
};

watch([type, period, level], ([newType, newPeriod, newLevel]) => {
    updateFilters({ type: newType, period: newPeriod, level: newLevel });
});

const updateSearch = debounce((value) => {
    updateFilters({ search: value });
}, 300);

watch(search, updateSearch);

const getLevelBadge = (level) => {
    const badges = {
        info: 'default',
        warning: 'warning',
        error: 'destructive'
    };
    return badges[level] || 'default';
};

const exportLogs = async () => {
    try {
        const response = await fetch(route('admin.logs.export', props.filters), {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': usePage().props.csrf_token
            }
        });
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `logs_export_${new Date().toISOString().slice(0,10)}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Export failed:', error);
    }
};
</script>

<template>
    <Head title="| System Logs" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-semibold tracking-tight">System Logs</h2>
            <Button @click="exportLogs" variant="outline" size="sm" class="gap-2">
                <Download class="h-4 w-4" />
                Export
            </Button>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-4">
            <Card v-for="(count, logType) in stats" :key="logType">
                <CardContent class="pt-6">
                    <div class="text-2xl font-bold">{{ count }}</div>
                    <p class="text-sm text-muted-foreground capitalize">
                        {{ logType }} Logs
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex-1 max-w-xs">
                <Input 
                    type="search" 
                    placeholder="Search logs..." 
                    v-model="search"
                    class="h-9"
                />
            </div>

            <div class="flex gap-2">
                <Select v-model="type">
                    <SelectTrigger class="h-9 w-[130px]">
                        <SelectValue placeholder="Type" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem 
                            v-for="option in typeOptions" 
                            :key="option.value" 
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="period">
                    <SelectTrigger class="h-9 w-[130px]">
                        <SelectValue placeholder="Period" />
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

                <Select v-model="level">
                    <SelectTrigger class="h-9 w-[130px]">
                        <SelectValue placeholder="Level" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem 
                            v-for="option in levelOptions" 
                            :key="option.value" 
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow class="hover:bg-transparent">
                        <TableHead class="w-[160px]">Time</TableHead>
                        <TableHead class="w-[100px]">Type</TableHead>
                        <TableHead class="w-[100px]">Level</TableHead>
                        <TableHead class="w-[140px]">Actor</TableHead>
                        <TableHead>Event</TableHead>
                        <TableHead class="w-[80px]"></TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-for="log in logs.data" :key="log.id">
                        <TableRow>
                            <TableCell class="font-mono text-xs text-muted-foreground">
                                {{ formatDateTime(log.created_at) }}
                            </TableCell>
                            <TableCell>
                                <Badge 
                                    variant="outline" 
                                    :class="{
                                        'border-blue-500 text-blue-600': log.log_type === 'system',
                                        'border-green-500 text-green-600': log.log_type === 'user',
                                        'border-purple-500 text-purple-600': log.log_type === 'admin',
                                        'border-red-500 text-red-600': log.log_type === 'error'
                                    }"
                                >
                                    {{ log.log_type }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge 
                                    :variant="getLevelBadge(log.level)"
                                    class="font-normal"
                                >
                                    {{ log.level }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ log.actor || 'System' }}
                            </TableCell>
                            <TableCell class="text-sm">
                                {{ log.description }}
                            </TableCell>
                            <TableCell>
                                <Button 
                                    v-if="log.properties"
                                    variant="ghost" 
                                    size="sm"
                                    class="h-8 px-2 text-xs"
                                    @click="log.showDetails = !log.showDetails"
                                >
                                    {{ log.showDetails ? '−' : '+' }}
                                </Button>
                            </TableCell>
                        </TableRow>
                        <!-- Details Row -->
                        <TableRow 
                            v-if="log.showDetails && log.properties"
                            class="hover:bg-transparent"
                        >
                            <TableCell colspan="6" class="p-0 border-t-0">
                                <div class="p-4 bg-muted/50 text-xs font-mono">
                                    <pre class="whitespace-pre-wrap">{{ JSON.stringify(log.properties, null, 2) }}</pre>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>
    </div>
</template>

<style scoped>
:deep(.select-content) {
    min-width: 140px;
}
</style>
