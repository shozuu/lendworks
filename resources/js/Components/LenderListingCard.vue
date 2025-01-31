<script setup>
import { computed } from "vue";
import { formatNumber, formatRentalDate } from "@/lib/formatters";
import BaseRentalCard from "@/Components/BaseRentalCard.vue";

const props = defineProps({
	data: {
		type: Object,
		required: true,
	},
	selectedStatus: {
		type: String,
		required: true,
	},
});

// Image handling
const listingImage = computed(() => {
	const image = props.data.listing?.images?.[0];
	return image?.image_path
		? `/storage/${image.image_path}`
		: "/storage/images/listing/default.png";
});

const details = computed(() => [
	{
		label: "Total",
		value: formatNumber(props.data.rental_request.total_price),
	},
	{
		label: "Period",
		value: `${formatRentalDate(
			props.data.rental_request.start_date
		)} - ${formatRentalDate(props.data.rental_request.end_date)}`,
	},
	{
		label: "Renter",
		value: props.data.rental_request.renter.name,
	},
]);
</script>

<template>
	<BaseRentalCard
		:title="data.listing.title"
		:image="listingImage"
		:status="data.rental_request.status"
		:listing-id="data.listing.id"
		:details="details"
	>
		<!-- Actions slot -->
		<template #actions>
			<div class="flex gap-2 mt-2">
				<!-- Future action buttons -->
			</div>
		</template>
	</BaseRentalCard>
</template>
