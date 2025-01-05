<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

defineOptions({ layout: AdminLayout });

defineProps({
    stats: Object
});
</script>

<template>
    <Head title="| Admin Dashboard" />
    
    <div class="space-y-6">
        <h2 class="text-2xl font-semibold tracking-tight">Dashboard Overview</h2>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-medium">
                        Total Listings
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats?.totalListings || 0 }}</div>
                </CardContent>
            </Card>

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

            <!-- Add more stat cards as needed -->
        </div>
    </div>
</template>
