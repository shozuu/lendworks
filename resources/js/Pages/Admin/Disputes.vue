<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Link } from "@inertiajs/vue3";
import { formatDateTime } from "@/lib/formatters";

defineOptions({ layout: AdminLayout });

defineProps({
    disputes: Object,
    stats: Object
});
</script>

<template>
    <Head title="Disputes | Admin" />

    <div class="space-y-6">
        <h2 class="text-2xl font-semibold tracking-tight">Disputes</h2>

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
                    <div v-for="dispute in disputes.data" :key="dispute.id" class="flex gap-4">
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
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
