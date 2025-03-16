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
            <Button @click="exportLogs" variant="outline" class="gap-2">
                <Download class="h-4 w-4" />
                Export Logs
            </Button>
        </div>

        <!-- Stats -->
        <div class="grid gap-4 md:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium">System Logs</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.system }}</div>
                </CardContent>
            </Card>
            <!-- Add similar cards for user, admin, and error logs -->
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-4">
            <div class="flex-1">
                <Input 
                    type="search" 
                    placeholder="Search logs..." 
                    v-model="search"
                />
            </div>

            <Select v-model="type">
                <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="Log Type" />
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
                <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="Time Period" />
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
                <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="Log Level" />
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

        <!-- Logs Table -->
        <Card>
            <CardContent class="p-0">
                <div class="space-y-4">
                    <div v-for="log in logs.data" :key="log.id" class="p-4 border-b last:border-0">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <Badge :variant="getLevelBadge(log.level)">
                                        {{ log.level }}
                                    </Badge>
                                    <span class="font-medium">{{ log.log_type }}</span>
                                </div>
                                <p class="text-sm">{{ log.description }}</p>
                                <pre v-if="log.properties" class="text-xs bg-muted p-2 rounded-md mt-2 overflow-x-auto">
                                    {{ JSON.stringify(log.properties, null, 2) }}
                                </pre>
                            </div>
                            <div class="text-sm text-muted-foreground whitespace-nowrap">
                                {{ formatDateTime(log.created_at) }}
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
