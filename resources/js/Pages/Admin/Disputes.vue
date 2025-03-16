<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Link } from "@inertiajs/vue3";
import { formatDate, formatDateTime } from "@/lib/formatters";
import { Button } from "@/components/ui/button";
import { Download } from "lucide-vue-next";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    disputes: Object,
    stats: Object
});

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
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-semibold tracking-tight">Disputes</h2>
            <Button @click="exportToCSV" variant="outline" class="gap-2">
                <Download class="h-4 w-4" />
                Export CSV
            </Button>
        </div>

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
