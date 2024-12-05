<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    listings: Array
});

const approveListing = (listingId) => {
    router.patch(route('admin.listings.approve', listingId));
};

const rejectListing = (listingId) => {
    router.patch(route('admin.listings.reject', listingId));
};
</script>

<template>
    <Head title="Manage Listings" />
    
    <Card>
        <CardHeader>
            <CardTitle>Pending Listings</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <div v-for="listing in listings" :key="listing.id" 
                    class="flex items-center justify-between p-4 border rounded-lg">
                    <div>
                        <h3 class="font-semibold">{{ listing.title }}</h3>
                        <p class="text-muted-foreground text-sm">by {{ listing.user.name }}</p>
                    </div>
                    <div class="space-x-2">
                        <Button variant="outline" @click="rejectListing(listing.id)">
                            Reject
                        </Button>
                        <Button @click="approveListing(listing.id)">
                            Approve
                        </Button>
                    </div>
                </div>
                <div v-if="!listings.length" class="text-muted-foreground py-4 text-center">
                    No pending listings to review
                </div>
            </div>
        </CardContent>
    </Card>
</template>