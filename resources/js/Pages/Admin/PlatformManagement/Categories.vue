<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { 
    Table, TableHeader, TableBody, TableRow, 
    TableHead, TableCell 
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/components/ui/dialog";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    categories: Array
});

const showAddDialog = ref(false);
const showEditDialog = ref(false);
const showDeleteDialog = ref(false);
const selectedCategory = ref(null);
const newCategoryName = ref('');
const editCategoryName = ref('');

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
    router.post(route('admin.platform.categories.store'), {
        name: newCategoryName.value
    }, {
        onSuccess: () => {
            showAddDialog.value = false;
            newCategoryName.value = '';
        }
    });
};

const handleEdit = () => {
    router.patch(route('admin.platform.categories.update', selectedCategory.value.id), {
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
    router.delete(route('admin.platform.categories.delete', selectedCategory.value.id), {
        onSuccess: () => {
            showDeleteDialog.value = false;
            selectedCategory.value = null;
        }
    });
};
</script>

<template>
    <Head title="| Manage Categories" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-semibold tracking-tight">Manage Categories</h2>
            <Button @click="openAddDialog">Add Category</Button>
        </div>

        <Card>
            <CardContent class="pt-6">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Slug</TableHead>
                            <TableHead>Total Listings</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="category in categories" :key="category.id">
                            <TableCell>{{ category.name }}</TableCell>
                            <TableCell>{{ category.slug }}</TableCell>
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
    </div>
</template>
