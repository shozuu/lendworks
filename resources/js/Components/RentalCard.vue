<script setup>
import { computed, ref } from "vue";
import { formatNumber, formatRentalDate } from "@/lib/formatters";
import { Button } from "@/components/ui/button";
import BaseRentalCard from "@/Components/BaseRentalCard.vue";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import { useForm } from "@inertiajs/vue3";
import RentalDetailsDialog from "@/Components/RentalDetailsDialog.vue";

const props = defineProps({
	rental: {
		type: Object,
		required: true,
	},
});

const showCancelDialog = ref(false);
const cancelForm = useForm({});

const handleCancel = () => {
	cancelForm.patch(route("rental-request.cancel", props.rental.id), {
		onSuccess: () => {
			showCancelDialog.value = false;
		},
	});
};

// computed property for image URL
const listingImage = computed(() => {
	const image = props.rental?.listing?.images?.[0];
	if (image?.image_path) {
		return `/storage/${image.image_path}`;
	}
	return "/storage/images/listing/default.png";
});

const details = computed(() => [
	{
		label: "Total",
		value: formatNumber(props.rental.total_price),
	},
	{
		label: "Period",
		value: `${formatRentalDate(props.rental.start_date)} - ${formatRentalDate(
			props.rental.end_date
		)}`,
	},
	{
		label: "Owner",
		value: props.rental.listing.user.name,
	},
]);

const showDetails = ref(false);
</script>

<template>
	<BaseRentalCard
		:title="rental.listing.title"
		:image="listingImage"
		:status="rental.status"
		:listing-id="rental.listing.id"
		:details="details"
		@click="showDetails = true"
	>
		<!-- Details slot -->
		<template #additional-details>
			<p v-if="rental.status === 'active'" class="text-muted-foreground text-sm">
				Due: {{ formatRentalDate(rental.end_date) }}
			</p>
		</template>

		<!-- Actions slot -->
		<template #actions>
			<div class="sm:justify-end flex flex-wrap gap-2">
				<!-- Show Cancel button for pending and approved statuses -->
				<Button
					v-if="['pending', 'approved'].includes(rental.status)"
					variant="destructive"
					size="sm"
					:disabled="cancelForm.processing"
					 @click.stop="showCancelDialog = true"
				>
					Cancel Request
				</Button>
			</div>
		</template>
	</BaseRentalCard>

	<RentalDetailsDialog
		v-model:show="showDetails"
		:rental="rental"
		user-role="renter"
		@cancel="showCancelDialog = true"
	/>

	<!-- Cancel Dialog -->
	<ConfirmDialog
		:show="showCancelDialog"
		title="Cancel Rental Request"
		description="Are you sure you want to cancel this rental request?"
		confirmLabel="Cancel Request"
		confirmVariant="destructive"
		:processing="cancelForm.processing"
		@update:show="showCancelDialog = $event"
		@confirm="handleCancel"
		@cancel="showCancelDialog = false"
	/>
</template>
