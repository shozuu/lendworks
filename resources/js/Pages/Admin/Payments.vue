<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { formatNumber } from "@/lib/formatters";
import { format } from "date-fns";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    rentals: Array
});

const verifyPayment = (rentalId) => {
    router.patch(route('admin.payments.verify', rentalId));
};

const releasePayment = (rentalId) => {
    router.patch(route('admin.payments.release', rentalId));
};
</script>

<template>
    <Head title="Payment Management" />
    
    <Card>
        <CardHeader>
            <CardTitle>Payment Management</CardTitle>
        </CardHeader>
        <CardContent>
            <div class="space-y-4">
                <div v-for="rental in rentals" :key="rental.id" class="p-4 border rounded-lg">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-semibold">{{ rental.listing.title }}</h3>
                            <div class="text-muted-foreground space-y-1 text-sm">
                                <p>Renter: {{ rental.renter.name }}</p>
                                <p>Amount: {{ formatNumber(rental.total_price) }}</p>
                            </div>
                        </div>
                        <div class="space-x-2">
                            <Button @click="verifyPayment(rental.id)">
                                Verify Payment
                            </Button>
                            <Button variant="outline" @click="releasePayment(rental.id)">
                                Release to Lender
                            </Button>
                        </div>
                    </div>
                </div>
                <div v-if="!rentals.length" class="text-muted-foreground py-4 text-center">
                    No pending payments to review
                </div>
            </div>
        </CardContent>
    </Card>
</template>
