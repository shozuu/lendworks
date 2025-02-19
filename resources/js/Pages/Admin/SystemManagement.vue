/**
 * SystemManagement Vue Component
 * Created: [Current Date]
 * 
 * Purpose:
 * Provides interface for admin system management including:
 * - System information display
 * - Server details
 * - Database status
 * - Cache management
 * - Storage permissions
 * - Maintenance mode controls
 * 
 * Features:
 * - Real-time status indicators
 * - Interactive system controls
 * - Responsive layout
 * - Error handling
 */

<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { AlertCircle, CheckCircle2 } from "lucide-vue-next";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    systemInfo: Object
});

const performAction = (action) => {
    router.post(route(`admin.system.${action}`));
};
</script>

<template>
    <Head title="| System Management" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-semibold tracking-tight">System Management</h2>
            <div class="space-x-2">
                <Button @click="performAction('clear-cache')">Clear Cache</Button>
                <Button @click="performAction('optimize')">Optimize System</Button>
                <Button 
                    :variant="systemInfo.maintenance_mode ? 'default' : 'destructive'"
                    @click="performAction('maintenance')"
                >
                    {{ systemInfo.maintenance_mode ? 'Disable' : 'Enable' }} Maintenance Mode
                </Button>
            </div>
        </div>

        <!-- System Information -->
        <div class="grid gap-4 md:grid-cols-2">
            <!-- Server Info -->
            <Card>
                <CardHeader>
                    <CardTitle>Server Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">PHP Version</span>
                        <span>{{ systemInfo.php_version }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Laravel Version</span>
                        <span>{{ systemInfo.laravel_version }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Server Software</span>
                        <span>{{ systemInfo.server_info }}</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Database Info -->
            <Card>
                <CardHeader>
                    <CardTitle>Database Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Database Name</span>
                        <span>{{ systemInfo.database.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Database Version</span>
                        <span>{{ systemInfo.database.version }}</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Cache Info -->
            <Card>
                <CardHeader>
                    <CardTitle>Cache Status</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Cache Driver</span>
                        <span>{{ systemInfo.cache.driver }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground">Cache Status</span>
                        <div class="flex items-center gap-2">
                            <CheckCircle2 v-if="systemInfo.cache.status !== 'Not Connected'" 
                                        class="w-5 h-5 text-green-500" />
                            <AlertCircle v-else class="w-5 h-5 text-red-500" />
                            {{ systemInfo.cache.status }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Storage Info -->
            <Card>
                <CardHeader>
                    <CardTitle>Storage Permissions</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground">Uploads Directory</span>
                        <div class="flex items-center gap-2">
                            <CheckCircle2 v-if="systemInfo.storage.uploads_dir" 
                                        class="w-5 h-5 text-green-500" />
                            <AlertCircle v-else class="w-5 h-5 text-red-500" />
                            {{ systemInfo.storage.uploads_dir ? 'Writable' : 'Not Writable' }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground">Logs Directory</span>
                        <div class="flex items-center gap-2">
                            <CheckCircle2 v-if="systemInfo.storage.logs_dir" 
                                        class="w-5 h-5 text-green-500" />
                            <AlertCircle v-else class="w-5 h-5 text-red-500" />
                            {{ systemInfo.storage.logs_dir ? 'Writable' : 'Not Writable' }}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
