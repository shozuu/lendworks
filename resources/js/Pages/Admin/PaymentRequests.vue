<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Badge } from "@/components/ui/badge";
import { Card } from "@/components/ui/card";
import { formatDate } from "@/lib/formatters";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { ref } from "vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    payments: Object,
    stats: Object
});

const selectedPayment = ref(null);

const verifyPayment = (payment) => {
    router.post(route('admin.payments.verify', payment.id), {}, {
        onSuccess: () => {
            selectedPayment.value = null;
        },
    });
};

const rejectPayment = (payment, feedback) => {
    router.post(route('admin.payments.reject', payment.id), {
        feedback: feedback
    }, {
        onSuccess: () => {
            selectedPayment.value = null;
        },
    });
};
</script>

<template>
    <Head title="| Admin - Payment Requests" />

    <div class="space-y-6">
        <!-- Header Section -->
        <div class="flex flex-col gap-4">
            <div class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-2">
                <div class="space-y-1">
                    <h2 class="text-2xl font-semibold tracking-tight">Payment Requests</h2>
                    <p class="text-muted-foreground text-sm">Review and verify payment submissions</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Badge variant="outline">Total: {{ stats.total }}</Badge>
                    <Badge variant="warning">Pending: {{ stats.pending }}</Badge>
                    <Badge variant="success">Verified: {{ stats.verified }}</Badge>
                    <Badge variant="destructive">Rejected: {{ stats.rejected }}</Badge>
                </div>
            </div>
        </div>

        <!-- Payments List -->
        <div v-if="payments.data.length" class="space-y-4">
            <Card v-for="payment in payments.data" :key="payment.id" class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <h3 class="font-medium">Reference #{{ payment.reference_number }}</h3>
                        <div class="text-muted-foreground text-sm">
                            <p>Submitted: {{ formatDate(payment.created_at) }}</p>
                            <p>Rental Request: #{{ payment.rental_request_id }}</p>
                            <p>Renter: {{ payment.rental_request.renter.name }}</p>
                            <p>Amount: ₱{{ payment.rental_request.total_price }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <Badge :variant="payment.status === 'pending' ? 'warning' : payment.status === 'verified' ? 'success' : 'destructive'">
                            {{ payment.status.charAt(0).toUpperCase() + payment.status.slice(1) }}
                        </Badge>

                        <div v-if="payment.status === 'pending'" class="flex gap-2">
                            <Button variant="outline" @click="selectedPayment = payment">
                                View Details
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>
            <PaginationLinks :paginator="payments" />
        </div>
        <div v-else class="text-muted-foreground py-10 text-center">
            No payment requests found
        </div>

        <!-- Payment Details Dialog -->
        <Dialog :open="!!selectedPayment" @update:open="selectedPayment = null">
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Payment Request Details</DialogTitle>
                    <DialogDescription>
                        Reference #{{ selectedPayment?.reference_number }}
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedPayment" class="space-y-4">
                    <!-- Payment Proof Image -->
                    <div class="aspect-video overflow-hidden border rounded-lg">
                        <img 
                            :src="`/storage/${selectedPayment.payment_proof_path}`" 
                            :alt="'Payment proof for ' + selectedPayment.reference_number"
                            class="object-contain w-full h-full"
                        />
                    </div>

                    <!-- Payment Info -->
                    <div class="space-y-1 text-sm">
                        <p><span class="font-medium">Submitted:</span> {{ formatDate(selectedPayment.created_at) }}</p>
                        <p><span class="font-medium">Renter:</span> {{ selectedPayment.rental_request.renter.name }}</p>
                        <p><span class="font-medium">Amount:</span> ₱{{ selectedPayment.rental_request.total_price }}</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-2 pt-4">
                        <Button variant="destructive" @click="rejectPayment(selectedPayment, 'Invalid payment proof submitted')">
                            Reject
                        </Button>
                        <Button @click="verifyPayment(selectedPayment)">
                            Verify Payment
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
