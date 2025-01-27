<script setup>
import { computed } from "vue";
import { Card, CardContent } from "@/components/ui/card";
import { Link } from "@inertiajs/vue3";
import { formatNumber, formatRentalDate } from "@/lib/formatters";
import RentalStatusBadge from "@/Components/RentalStatusBadge.vue";

const props = defineProps({
	listing: {
		type: Object,
		required: true,
	},
	selectedStatus: {
		type: String,
		required: true,
	},
});

// Get the most recent rental request for this status
const activeRental = computed(() => {
	// Since listings are pre-filtered, we just need the latest matching rental request
	return props.listing.rental_requests[0];
});

// Image handling
const listingImage = computed(() => {
	const image = props.listing?.images?.[0];
	return image?.image_path
		? `/storage/${image.image_path}`
		: "/storage/images/listing/default.png";
});
</script>

<template>
	<Card>
		<CardContent class="sm:p-6 p-4">
			<div class="grid gap-4 sm:grid-cols-[1fr_120px]">
				<!-- Rental Info -->
				<div class="space-y-3">
					<!-- Title and Status -->
					<div class="space-y-2">
						<Link
							:href="route('listing.show', listing.id)"
							class="hover:underline font-semibold"
						>
							{{ listing.title }}
						</Link>
						<div v-if="activeRental">
							<RentalStatusBadge :status="activeRental.status" />
						</div>
					</div>

					<!-- Rental Details -->
					<div v-if="activeRental" class="text-muted-foreground text-sm space-y-1">
						<p>
							{{ formatRentalDate(activeRental.start_date) }} -
							{{ formatRentalDate(activeRental.end_date) }}
						</p>
						<p>Total: {{ formatNumber(activeRental.total_price) }}</p>
					</div>

					<!-- Renter Info -->
					<div v-if="activeRental" class="text-sm">
						<p>Renter: {{ activeRental.renter.name }}</p>
					</div>
				</div>

				<!-- Listing Image -->
				<div class="sm:order-last order-first aspect-square overflow-hidden rounded-lg">
					<img
						:src="listingImage"
						:alt="listing.title"
						class="object-cover w-full h-full"
					/>
				</div>
			</div>
		</CardContent>
	</Card>
</template>
