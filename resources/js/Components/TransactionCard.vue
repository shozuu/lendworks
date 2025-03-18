<script setup>
import { Link } from "@inertiajs/vue3";
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { formatDate } from "@/lib/formatters";

const props = defineProps({
    transaction: {
        type: Object,
        required: true
    },
    statusBadge: {
        type: Object,
        required: true
    }
});
</script>

<template>
    <Card class="sm:p-6 hover:ring-1 hover:ring-primary/20 group p-4 transition-all cursor-pointer">
        <Link :href="route('admin.rental-transactions.show', transaction.id)" class="block">
            <!-- Add relative positioning for badge placement -->
            <div class="relative">
                <!-- Status Badge - Positioned top right -->
                <div class="absolute top-0 right-0 z-10">
                    <Badge :variant="statusBadge.variant">
                        {{ statusBadge.label }}
                    </Badge>
                </div>

                <div class="sm:flex-row flex flex-col gap-4">
                    <!-- Add Image -->
                    <div class="sm:w-32 sm:h-32 shrink-0 w-24 h-24 overflow-hidden rounded-md">
                        <img
                            :src="
                                transaction.listing.images[0]?.image_path
                                    ? `/storage/${transaction.listing.images[0].image_path}`
                                    : '/storage/images/listing/default.png'
                            "
                            :alt="transaction.listing.title"
                            class="object-cover w-full rounded-lg"
                        />
                    </div>

                    <!-- Transaction Info -->
                    <div class="flex-1 min-w-0 space-y-2">
                        <h3 class="group-hover:text-primary font-semibold truncate transition-colors">
                            {{ transaction.listing.title }}
                        </h3>
                        <div class="sm:text-sm text-muted-foreground space-y-1 text-xs">
                            <p><span class="font-medium">Lender:</span> {{ transaction.listing.user.name }}</p>
                            <p><span class="font-medium">Renter:</span> {{ transaction.renter.name }}</p>
                            <p><span class="font-medium">Created:</span> {{ formatDate(transaction.created_at) }}</p>
                            <p><span class="font-medium">Total Price:</span> ₱{{ transaction.total_price }}</p>
                            
                        </div>
                    </div>
                </div>
            </div>
        </Link>
    </Card>
</template>
