<script setup>
import { computed, ref } from "vue";
import { formatNumber, formatRentalDate } from "@/lib/formatters";
import BaseRentalCard from "@/Components/BaseRentalCard.vue";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";

const props = defineProps({
	data: {
		type: Object,
		required: true,
	},
	selectedStatus: {
		type: String,
		required: true,
	},
	rejectionReasons: {
		type: Array,
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

const showRejectDialog = ref(false);
const showAcceptDialog = ref(false);
const approveForm = useForm({});
const rejectForm = useForm({
	rejection_reason_id: "",
	custom_feedback: "",
});

const isOtherReason = computed(() => {
	return rejectForm.rejection_reason_id === "other";
});

const handleApprove = () => {
	approveForm.patch(route("rental-request.approve", props.data.rental_request.id), {
		preserveScroll: true,
		onSuccess: () => {
			showAcceptDialog.value = false;
		},
	});
};

const handleReject = () => {
	rejectForm.patch(route("rental-request.reject", props.data.rental_request.id), {
		onSuccess: () => {
			showRejectDialog.value = false;
		},
		preserveScroll: true,
	});
};
</script>

<template>
	<BaseRentalCard
		:title="data.listing.title"
		:image="listingImage"
		:status="data.rental_request.status"
		:listing-id="data.listing.id"
		:details="details"
		 @click="$inertia.visit(route('rental.show', data.rental_request.id))"
	>
		<!-- Actions slot -->
		<template #actions>
			<div class="sm:justify-end flex flex-wrap gap-2">
				<Button
					v-if="data.rental_request.status === 'pending'"
					variant="default"
					size="sm"
					:disabled="approveForm.processing"
					 @click.stop="showAcceptDialog = true"
				>
					{{ approveForm.processing ? "Approving..." : "Approve" }}
				</Button>

				<Button
					v-if="data.rental_request.status === 'pending'"
					variant="destructive"
					size="sm"
					:disabled="rejectForm.processing"
					 @click.stop="showRejectDialog = true"
				>
					Reject
				</Button>
			</div>
		</template>
	</BaseRentalCard>

	<!-- Accept Dialog -->
	<ConfirmDialog
		:show="showAcceptDialog"
		title="Accept Rental Request"
		description="Are you sure you want to accept this rental request? This will mark your item as rented and reject all other pending requests."
		confirmLabel="Accept Request"
		confirmVariant="default"
		:processing="approveForm.processing"
		@update:show="showAcceptDialog = $event"
		@confirm="handleApprove"
		@cancel="showAcceptDialog = false"
	/>

	<!-- Reject Dialog -->
	<ConfirmDialog
		:show="showRejectDialog"
		title="Reject Rental Request"
		z
		description="Please select a reason for rejecting this rental request."
		confirmLabel="Reject Request"
		confirmVariant="destructive"
		:processing="rejectForm.processing"
		:disabled="
			!rejectForm.rejection_reason_id || (isOtherReason && !rejectForm.custom_feedback)
		"
		showSelect
		:selectOptions="rejectionReasons"
		:selectValue="rejectForm.rejection_reason_id"
		:showTextarea="isOtherReason"
		:textareaValue="rejectForm.custom_feedback"
		:textareaRequired="isOtherReason"
		textareaPlaceholder="Please provide specific details about why you are rejecting this rental request..."
		@update:show="showRejectDialog = $event"
		@update:selectValue="rejectForm.rejection_reason_id = $event"
		@update:textareaValue="rejectForm.custom_feedback = $event"
		@confirm="handleReject"
		@cancel="showAcceptDialog = false"
	/>
</template>
