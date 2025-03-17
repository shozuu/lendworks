/**
 * SystemManagement Vue Component
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
 * - status indicators
 * 
 * Change Log:
 * 1. Added tab system to switch between System and Platform management
 * 2. Integrated category management UI into platform tab
 * 3. Added CRUD operations for categories
 * 4. Added modal dialogs for add/edit/delete operations
 * 5. Added form validation and error handling
 * 6. Added category listing with actions
 * 7. Added proper state management for category operations
 * 8. Added proper route handling for category actions
 * 9. Added disable logic for categories with listings
 */

<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { AlertCircle, CheckCircle2 } from "lucide-vue-next";
import { ref } from 'vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Input } from "@/components/ui/input";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    systemInfo: Object,
    categories: Array
});

const activeTab = ref('system');

const performAction = (action) => {
    router.post(route(`admin.system.${action}`));
};

// Category management state
const showAddDialog = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedCategory = ref(null);
const newCategoryName = ref('');
const editCategoryName = ref('');

// Category management methods
const openAddDialog = () => {
    newCategoryName.value = '';
    showAddDialog.value = true;
};

const openEditDialog = (category) => {
    selectedCategory.value = category;
    editCategoryName.value = category.name;
    showEditDialog.value = true;
};

const openDeleteDialog = (category) => {
    selectedCategory.value = category;
    showDeleteDialog.value = true;
};

const handleAdd = () => {
    router.post(route('admin.system.categories.store'), {
        name: newCategoryName.value
    }, {
        onSuccess: () => {
            showAddDialog.value = false;
            newCategoryName.value = '';
        }
    });
};

const handleEdit = () => {
    router.patch(route('admin.system.categories.update', selectedCategory.value.id), {
        name: editCategoryName.value
    }, {
        onSuccess: () => {
            showEditDialog.value = false;
            selectedCategory.value = null;
            editCategoryName.value = '';
        }
    });
};

const handleDelete = () => {
    router.delete(route('admin.system.categories.delete', selectedCategory.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            selectedCategory.value = null;
        }
    });
};

// Maintenance mode dialog state and methods
const showMaintenanceDialog = ref(false);

const toggleMaintenance = () => {
    if (!props.systemInfo.maintenance_mode) {
        // Show confirmation dialog when enabling maintenance mode
        showMaintenanceDialog.value = true;
    } else {
        // Directly disable maintenance mode
        performAction('maintenance');
    }
};

const confirmMaintenance = () => {
    performAction('maintenance');
    showMaintenanceDialog.value = false;
};
</script>

<template>
    <Head title="| System Management" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-semibold tracking-tight">System Management</h2>
        </div>

        <Tabs v-model="activeTab" class="space-y-4">
            <TabsList>
                <TabsTrigger value="system">System Information</TabsTrigger>
                <TabsTrigger value="platform">Platform Management</TabsTrigger>
            </TabsList>

            <TabsContent value="system" class="space-y-4">
                <!-- Existing System Management Content -->
                <div class="space-x-2">
                    <Button @click="performAction('clear-cache')">Clear Cache</Button>
                    <Button 
                        :variant="systemInfo.maintenance_mode ? 'default' : 'destructive'"
                        @click="toggleMaintenance"
                    >
                        {{ systemInfo.maintenance_mode ? 'Disable' : 'Enable' }} Maintenance Mode
                    </Button>
                </div>

                <!-- System Information Grid -->
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
            </TabsContent>

            <TabsContent value="platform" class="space-y-4">
                <!-- Category Management Content -->
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium">Manage Categories</h3>
                    <Button @click="openAddDialog">Add Category</Button>
                </div>

                <Card>
                    <CardContent class="pt-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <!--<TableHead>Slug</TableHead>-->
                                    <TableHead>Total Listings</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="category in categories" :key="category.id">
                                    <TableCell>{{ category.name }}</TableCell>
                                    <!--<TableCell>{{ category.slug }}</TableCell>-->
                                    <TableCell>{{ category.listings_count }}</TableCell>
                                    <TableCell class="text-right space-x-2">
                                        <Button 
                                            variant="outline" 
                                            size="sm"
                                            @click="openEditDialog(category)"
                                        >
                                            Edit
                                        </Button>
                                        <Button 
                                            variant="destructive" 
                                            size="sm"
                                            :disabled="category.listings_count > 0"
                                            @click="openDeleteDialog(category)"
                                        >
                                            Delete
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <!-- Add Category Dialog -->
                <Dialog v-model:open="showAddDialog">
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Add New Category</DialogTitle>
                        </DialogHeader>
                        <div class="py-4">
                            <Input 
                                v-model="newCategoryName" 
                                placeholder="Category name"
                                @keyup.enter="handleAdd"
                            />
                        </div>
                        <DialogFooter>
                            <Button variant="outline" @click="showAddDialog = false">
                                Cancel
                            </Button>
                            <Button @click="handleAdd">Add Category</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <!-- Edit Category Dialog -->
                <Dialog v-model:open="showEditDialog">
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Edit Category</DialogTitle>
                        </DialogHeader>
                        <div class="py-4">
                            <Input 
                                v-model="editCategoryName" 
                                placeholder="Category name"
                                @keyup.enter="handleEdit"
                            />
                        </div>
                        <DialogFooter>
                            <Button variant="outline" @click="showEditDialog = false">
                                Cancel
                            </Button>
                            <Button @click="handleEdit">Save Changes</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <!-- Delete Category Dialog -->
                <Dialog v-model:open="showDeleteDialog">
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete Category</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to delete this category? This action cannot be undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" @click="showDeleteDialog = false">
                                Cancel
                            </Button>
                            <Button variant="destructive" @click="handleDelete">
                                Delete
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </TabsContent>
        </Tabs>
    </div>

    <!-- Add Maintenance Mode Confirmation Dialog -->
    <Dialog v-model:open="showMaintenanceDialog">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Enable Maintenance Mode</DialogTitle>
                <DialogDescription>
                    Are you sure you want to enable maintenance mode? This will:
                    <ul class="list-disc pl-4 mt-2 space-y-1">
                        <li>Make the site inaccessible to regular users</li>
                        <li>Show a maintenance page to all visitors</li>
                        <li>Only administrators can access the site</li>
                        <li>All ongoing user sessions will be interrupted</li>
                    </ul>
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showMaintenanceDialog = false">
                    Cancel
                </Button>
                <Button variant="destructive" @click="confirmMaintenance">
                    Enable Maintenance Mode
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
