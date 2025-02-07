<script setup>
import BaseListingCard from "./BaseListingCard.vue";
import { Button } from "@/components/ui/button";
import { XCircle } from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import { formatNumber } from "@/lib/formatters";

const props = defineProps({
	listing: {
		type: Object,
		required: true,
	},
	rejectionReasons: {
		type: Array,
		required: true,
	},
});
const emit = defineEmits(["updateStatus"]);

const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const selectedReason = ref("");
const isApproving = ref(false);
const isRejecting = ref(false);
const customFeedback = ref("");

const handleUpdateStatus = async (status) => {
	if (status === "rejected" && !selectedReason.value) {
		return;
	}

	if (status === "approved") {
		if (isApproving.value) return;
		isApproving.value = true;

		await router.patch(
			route("admin.listings.approve", props.listing.id),
			{},
			{
				onSuccess: () => {
					showApproveDialog.value = false;
				},
				onFinish: () => {
					isApproving.value = false;
				},
				preserveScroll: true,
			}
		);
	} else if (status === "rejected") {
		if (isRejecting.value) return;
		isRejecting.value = true;

		// Check if "Other" reason requires custom feedback
		const isOtherReason =
			props.rejectionReasons.find((r) => r.value === selectedReason.value)?.code ===
			"other";

		if (isOtherReason && !customFeedback.value.trim()) return;

		await router.patch(
			route("admin.listings.reject", props.listing.id),
			{
				rejection_reason: selectedReason.value,
				feedback: customFeedback.value,
			},
			{
				onSuccess: () => {
					showRejectDialog.value = false;
					selectedReason.value = "";
					customFeedback.value = "";
				},
				onFinish: () => {
					isRejecting.value = false;
				},
				preserveScroll: true,
			}
		);
	}
};
</script>

<template>
	<BaseListingCard :listing="listing">
		<!-- Banner slot -->
		<template
			#banner
			v-if="
				(listing.status === 'rejected' && listing.latest_rejection) ||
				(listing.status === 'taken_down' && listing.latest_takedown)
			"
		>
			<div class="bg-destructive/10 p-3 text-sm">
				<div
					class="flex flex-col items-center gap-2 text-destructive sm:flex-row sm:items-start"
				>
					<div class="flex items-center gap-2">
						<XCircle class="shrink-0 w-4 h-4" />
						<p class="font-medium">
							{{ listing.status === "rejected" ? "Rejection" : "Takedown" }} Reason:
						</p>
					</div>
					{{
						listing.status === "rejected"
							? listing.latest_rejection.rejection_reason.label
							: listing.latest_takedown.takedown_reason.label
					}}
				</div>
			</div>
		</template>

		<!-- thumbnail, content (title and status), and details are in BaseListingCard  -->

		<!-- details unique to admins -->
		<template #extra-details>
			<div class="flex items-center gap-1">
				<p>Owner:</p>
				<Link
					:href="route('admin.users.show', listing.user.id)"
					class="hover:underline text-sm"
				>
					{{ listing.user.name }}
				</Link>
			</div>
			<p>Security Deposit: {{ formatNumber(listing.deposit_fee) }}</p>
		</template>

		<!-- Actions slot -->
		<template #actions v-if="listing.status === 'pending'">
			<div class="flex flex-wrap gap-2 sm:justify-end">
				<Button variant="default" size="sm" @click="showApproveDialog = true">
					Approve
				</Button>
				<Button variant="destructive" size="sm" @click="showRejectDialog = true">
					Reject
				</Button>
			</div>
		</template>
	</BaseListingCard>

	<!-- Dialogs -->
	<ConfirmDialog
		:show="showApproveDialog"
		title="Approve Listing"
		description="Are you sure you want to approve this listing? It will become visible to all users."
		confirmLabel="Approve"
		confirmVariant="default"
		:processing="isApproving"
		@update:show="showApproveDialog = $event"
		@confirm="handleUpdateStatus('approved')"
		@cancel="
			() => {
				showApproveDialog = false;
			}
		"
	/>

	<ConfirmDialog
		:show="showRejectDialog"
		title="Reject Listing"
		description="Please select a reason for rejecting this listing."
		confirmLabel="Reject"
		confirmVariant="destructive"
		:processing="isRejecting"
		:disabled="!selectedReason"
		showSelect
		:selectOptions="rejectionReasons"
		:selectValue="selectedReason"
		:textareaValue="customFeedback"
		textareaPlaceholder="Please provide specific details about why this listing was rejected..."
		@update:show="showRejectDialog = $event"
		@update:selectValue="selectedReason = $event"
		@update:textareaValue="customFeedback = $event"
		@confirm="handleUpdateStatus('rejected')"
		@cancel="
			() => {
				showRejectDialog = false;
				selectedReason = '';
				customFeedback = '';
			}
		"
	/>
</template>
