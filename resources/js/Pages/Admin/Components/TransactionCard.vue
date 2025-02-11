<script setup>
import { Card } from "@/components/ui/card";
import RentalStatusBadge from "@/Components/RentalStatusBadge.vue";
import { formatNumber, timeAgo } from "@/lib/formatters";
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    transaction: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <Card>
        <Link 
            :href="route('admin.rental-transactions.show', transaction.id)"
            class="hover:bg-muted/50 block p-6 transition-colors"
        >
            <div class="sm:flex-row flex flex-col gap-4">
                <!-- Details -->
                <div class="flex-1 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-medium">{{ transaction.listing.title }}</h3>
                        <RentalStatusBadge :status="transaction.status" />
                    </div>

                    <div class="text-muted-foreground grid gap-1 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">Renter:</span>
                            {{ transaction.renter.name }}
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium">Lender:</span>
                            {{ transaction.listing.user.name }}
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium">Amount:</span>
                            {{ formatNumber(transaction.total_price) }}
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium">Created:</span>
                            {{ timeAgo(transaction.created_at) }}
                        </div>
                    </div>
                </div>
            </div>
        </Link>
    </Card>
</template>
