<script setup>
import { computed } from 'vue'; // Add this import
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Link } from "@inertiajs/vue3";
import { formatNumber } from "@/lib/formatters";

const props = defineProps({
	listing: {
		type: Object,
		required: true,
	},
});

const emit = defineEmits(["toggleAvailability"]);

const handleToggleAvailability = () => {
	emit("toggleAvailability", props.listing.id);
};

const listingStatus = computed(() => {
    if (!props.listing.approved) {
        return {
            label: 'Pending Approval',
            variant: 'yellow'
        };
    }
    if (props.listing.is_currently_rented > 0) {
        return {
            label: 'Currently Rented',
            variant: 'orange'
        };
    }
    return {
        label: props.listing.is_available ? 'Available' : 'Not Available',
        variant: props.listing.is_available ? 'success' : 'secondary'
    };
});

const currentRental = computed(() => 
    props.listing.rentals?.find(r => [3, 4].includes(r.rental_status_id))
);
</script>

<template>
	<Card>
		<CardContent class="sm:p-6 p-4">
			<div class="grid gap-4 sm:grid-cols-[1fr_120px]">
				<div class="space-y-3">
					<div class="space-y-2">
						<Link
							:href="route('listing.show', listing.id)"
							class="hover:underline font-semibold"
						>
							{{ listing.title }}
						</Link>
							<div class="flex items-center justify-between">
								<div class="space-y-1">
									<Badge :variant="listingStatus.variant">
										{{ listingStatus.label }}
									</Badge>
									<div v-if="currentRental" class="text-muted-foreground text-sm">
										Rented by: {{ currentRental.renter.name }}
									</div>
								</div>
							</div>
					</div>

					<div class="text-muted-foreground text-sm">
						<p>Daily Rate: {{ formatNumber(listing.price) }}</p>
						<p class="mt-1">Category: {{ listing.category.name }}</p>
					</div>

					<!-- Toggle availability button -->
					<div class="flex gap-2 pt-2" v-if="!listing.is_currently_rented">
						<Button
							size="sm"
							:variant="listing.is_available ? 'outline' : 'default'"
							@click.prevent="handleToggleAvailability"
						>
							{{ listing.is_available ? "Make Unavailable" : "Make Available" }}
						</Button>
					</div>
				</div>

				<!-- Listing Image -->
				<div class="sm:order-last aspect-square order-first overflow-hidden rounded-md">
					<img
						:src="listing.images[0]?.url || '/storage/images/listing/default.png'"
						:alt="listing.title"
						class="object-cover w-full h-full"
					/>
				</div>
			</div>
		</CardContent>
	</Card>
</template>
