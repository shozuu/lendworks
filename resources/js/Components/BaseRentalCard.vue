<script setup>
import { Card, CardContent } from "@/components/ui/card";
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

const emit = defineEmits(["click"]);
</script>

<template>
	<Card
		@click="$emit('click')"
		class="cursor-pointer hover:bg-muted/50 transition-colors"
	>
		<CardContent class="sm:p-6 p-4">
			<div class="sm:flex-row flex flex-col gap-4">
				<!-- thumbnail - removed Link wrapper -->
				<div class="sm:w-32 sm:h-32 shrink-0 w-24 h-24 overflow-hidden rounded-md">
					<img :src="image" :alt="title" class="object-cover w-full h-full" />
				</div>

				<!-- Content section -->
				<div class="flex flex-col flex-1 gap-3">
					<!-- Title and Status - removed Link -->
					<div class="sm:flex-row flex flex-col items-start justify-between gap-1">
						<h3 class="line-clamp-1 font-semibold">{{ title }}</h3>
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

							<span v-if="index === 0" class="text-primary">
								{{ detail.value }}
							</span>
							<span v-else>{{ detail.value }}</span>
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
