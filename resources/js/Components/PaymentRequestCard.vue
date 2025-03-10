<script setup>
import { Badge } from "@/components/ui/badge";
import { formatDate } from "@/lib/formatters";

defineProps({
    payment: {
        type: Object,
        required: true
    }
});

const getStatusVariant = (status) => {
    switch (status) {
        case 'verified':
            return 'success';
        case 'rejected':
            return 'destructive';
        case 'pending':
            return 'warning';
        default:
            return 'secondary';
    }
};
</script>

<template>
    <div class="bg-background border p-4 rounded-lg hover:bg-accent/5 transition-colors">
        <div class="flex items-start justify-between gap-4">
            <!-- Left side: Payment info -->
            <div class="space-y-1 flex-1">
                <div class="flex items-center gap-2">
                    <h3 class="font-medium">Reference #{{ payment.reference_number }}</h3>
                    <Badge :variant="getStatusVariant(payment.status)">
                        {{ payment.status }}
                    </Badge>
                    <Badge v-if="payment.type === 'overdue'" variant="destructive">
                        Overdue Fee
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    Submitted {{ formatDate(payment.created_at) }}
                </p>
            </div>

            <!-- Right side: Renter info -->
            <div class="text-sm text-right">
                <p class="font-medium">{{ payment.rental_request?.renter?.name }}</p>
                <p class="text-muted-foreground">Renter</p>
            </div>
        </div>
    </div>
</template>
