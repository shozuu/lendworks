<script setup>
import { computed, ref } from "vue";
import { formatNumber, formatRentalDate } from "@/lib/formatters";
import { Button } from "@/components/ui/button";
import BaseRentalCard from "@/Components/BaseRentalCard.vue";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import PaymentDialog from "@/Components/PaymentDialog.vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
	rental: {
		type: Object,
		required: true,
	},
	cancellationReasons: {
		type: Array,
		required: true,
	},
});

const showCancelDialog = ref(false);
const showPaymentDialog = ref(false);

// Update the cancelForm to include reason
const cancelForm = useForm({
	cancellation_reason_id: "",
	custom_feedback: "",
});

const isOtherReason = computed(() => {
	return cancelForm.cancellation_reason_id === "other";
});

const handleCancel = () => {
	cancelForm.patch(route("rental-request.cancel", props.rental.id), {
		onSuccess: () => {
			showCancelDialog.value = false;
			// Reset form
			cancelForm.cancellation_reason_id = "";
			cancelForm.custom_feedback = "";
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

// computed property to check if rental has payment
const payment = computed(() => props.rental.payment_request);

// list of actions available for the rental as defined in the model
const actions = computed(() => props.rental.available_actions);
</script>

<template>
	<BaseRentalCard
		:title="rental.listing.title"
		:image="listingImage"
		:status="rental.status"
		:listing-id="rental.listing.id"
		:details="details"
		@click="$inertia.visit(route('rental.show', rental.id))"
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
				<!-- Payment Actions -->
				<Button
					v-if="actions.canPayNow || actions.canViewPayment"
					variant="default"
					size="sm"
					@click.stop="showPaymentDialog = true"
				>
					{{ payment ? "View Payment" : "Pay Now" }}
				</Button>

				<!-- Cancel Action -->
				<Button
					v-if="actions.canCancel"
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

	<!-- Cancel Dialog -->
	<ConfirmDialog
		:show="showCancelDialog"
		title="Cancel Rental Request"
		description="Please select a reason for cancelling this rental request."
		confirmLabel="Cancel Request"
		confirmVariant="destructive"
		:processing="cancelForm.processing"
		:disabled="
			!cancelForm.cancellation_reason_id || (isOtherReason && !cancelForm.custom_feedback)
		"
		showSelect
		:selectOptions="cancellationReasons"
		:selectValue="cancelForm.cancellation_reason_id"
		:showTextarea="isOtherReason"
		:textareaValue="cancelForm.custom_feedback"
		:textareaRequired="isOtherReason"
		textareaPlaceholder="Please provide specific details about why you are cancelling this rental request..."
		@update:show="showCancelDialog = $event"
		@update:selectValue="cancelForm.cancellation_reason_id = $event"
		@update:textareaValue="cancelForm.custom_feedback = $event"
		@confirm="handleCancel"
		@cancel="showCancelDialog = false"
	/>

	<!-- Payment Dialog -->
	<PaymentDialog v-model:show="showPaymentDialog" :rental="rental" :payment="payment" />
</template>
