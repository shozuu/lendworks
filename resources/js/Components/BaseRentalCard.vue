<script setup>
import { Card, CardContent } from "@/components/ui/card";
import { Link } from "@inertiajs/vue3";
import RentalStatusBadge from "@/Components/RentalStatusBadge.vue";

defineProps({
	title: String,
	image: String,
	status: String,
	listingId: Number,
	details: {
		type: Array,
		default: () => [],
		// expects array of { label: String, value: String }
	},
	statusText: String,
});
</script>

<template>
	<Card>
		<CardContent class="sm:p-6 p-4">
			<div class="sm:flex-row flex flex-col gap-4">
				<!-- thumbnail -->
				<div class="sm:w-32 sm:h-32 shrink-0 w-24 h-24 overflow-hidden rounded-md">
					<Link :href="route('listing.show', listingId)" class="h-full">
						<img :src="image" :alt="title" class="object-cover w-full h-full" />
					</Link>
				</div>

				<!-- Content section -->
				<div class="flex flex-col flex-1 gap-3">
					<!-- Title and Status -->
					<div class="sm:flex-row flex flex-col items-start justify-between gap-1">
						<Link
							:href="route('listing.show', listingId)"
							class="hover:underline line-clamp-1 font-semibold"
						>
							{{ title }}
						</Link>
						<RentalStatusBadge :status="status" />
					</div>

					<!-- Status Text if provided -->
					<div v-if="statusText" class="text-muted-foreground space-y-1 text-sm">
						<p>{{ statusText }}</p>
					</div>

					<!-- Standardized Details Format -->
					<div class="text-muted-foreground text-sm">
						<div v-for="(detail, index) in details" :key="index" class="flex gap-2">
							<span class="font-medium">{{ detail.label }}:</span>
							<span>{{ detail.value }}</span>
						</div>
					</div>

					<!-- Additional Details Slot -->
					<slot name="additional-details" />

					<!-- Actions Section -->
					<slot name="actions" />
				</div>
			</div>
		</CardContent>
	</Card>
</template>
