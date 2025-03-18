<script setup>
import { Link } from "@inertiajs/vue3";
import { formatDate } from "@/lib/formatters";  // Remove formatPrice as it's not being used
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ChevronRight } from "lucide-vue-next";

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

// Add safe getter method for image
const getListingImage = () => {
    if (props.transaction?.listing?.images?.length) {
        return `/storage/${props.transaction.listing.images[0].image_path}`;
    }
    return '/storage/images/listing/default.png'; // Fallback image
};

// Add safe getter methods for other properties
const getListingTitle = () => props.transaction?.listing?.title || 'Untitled Listing';
const getRenterName = () => props.transaction?.renter?.name || 'Unknown Renter';
const getLenderName = () => props.transaction?.listing?.user?.name || 'Unknown Lender';
</script>

<template>
    <div class="border rounded-lg hover:bg-muted group transition-colors">
        <Link :href="route('admin.rental-transactions.show', transaction.id)" class="block space-y-4 p-4">
            <!-- Status Badge -->
            <div class="flex justify-end mb-2">
                <Badge :variant="statusBadge.variant">
                    {{ statusBadge.label }}
                </Badge>
            </div>

            <div class="sm:flex-row flex flex-col gap-4">
                <!-- Listing Image & Details -->
                <div class="flex gap-4 flex-1">
                    <img 
                        :src="getListingImage()" 
                        :alt="getListingTitle()"
                        class="object-cover w-20 h-20 rounded-md"
                    />
                    <div class="space-y-1 flex-1 min-w-0">
                        <h3 class="font-medium truncate">{{ getListingTitle() }}</h3>
                        <div class="text-muted-foreground text-sm space-y-1">
                            <p>Renter: {{ getRenterName() }}</p>
                            <p>Lender: {{ getLenderName() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Date -->
                <div class="text-muted-foreground text-sm">
                    {{ formatDate(transaction.created_at) }}
                </div>
            </div>

            <!-- View Details Button -->
            <div class="flex justify-end">
                <Button variant="ghost" size="sm" class="gap-1">
                    View Details
                    <ChevronRight class="w-4 h-4" />
                </Button>
            </div>
        </Link>
    </div>
</template>
