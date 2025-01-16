<script setup>
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { formatNumber } from "@/lib/formatters";
import { Tags, MapPin, PhilippinePeso } from "lucide-vue-next";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";

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

const getStatusBadge = (listing) => {
	switch (listing.status) {
		case "approved":
			return {
				label: listing.is_available ? "Available" : "Not Available",
				variant: listing.is_available ? "success" : "destructive",
			};
		case "rejected":
			return {
				label: "Rejected",
				variant: "destructive",
			};
		case "pending":
		default:
			return {
				label: "Pending Approval",
				variant: "warning",
			};
	}
};
</script>

<template>
	<Card class="overflow-hidden">
		<div class="flex items-center gap-4 p-4">
			<!-- Thumbnail -->
			<div
				class="shrink-0 sm:h-32 sm:w-32 w-24 h-24 overflow-hidden rounded-md self-start"
			>
				<Link :href="route('admin.listings.show', listing.id)">
					<img
						:src="
							listing.images[0]
								? `/storage/${listing.images[0].image_path}`
								: '/storage/images/listing/default.png'
						"
						:alt="listing.title"
						class="object-cover w-full h-full"
					/>
				</Link>
			</div>

			<!-- Content -->
			<div class="flex flex-col flex-1 gap-2">
				<div class="flex items-start justify-between gap-4">
					<div class="space-y-1">
						<Link
							:href="route('admin.listings.show', listing.id)"
							class="hover:underline line-clamp-1 font-semibold"
						>
							{{ listing.title }}
						</Link>
						<div class="text-muted-foreground flex flex-col gap-1 text-sm">
							<div class="flex items-center gap-1">
								<Tags class="w-4 h-4" />
								{{ listing.category?.name }}
							</div>
							<div class="flex items-center gap-1">
								<PhilippinePeso class="w-4 h-4" />
								{{ formatNumber(listing.price) }}/day
							</div>
							<div class="flex items-center gap-1">
								<MapPin class="shrink-0 w-4 h-4" />
								<span class="line-clamp-1">
									{{ listing.location?.address ?? "No address specified" }},
									{{ listing.location?.city ?? "No city specified" }}
								</span>
							</div>
						</div>
						<div class="flex items-center gap-2 pt-1">
							<span class="text-sm">Owner:</span>
							<Link
								:href="route('admin.users.show', listing.user.id)"
								class="text-primary hover:underline text-sm"
							>
								{{ listing.user.name }}
							</Link>
						</div>
					</div>
					<Badge :variant="getStatusBadge(listing).variant">
						{{ getStatusBadge(listing).label }}
					</Badge>
				</div>

				<!-- actions -->
				<div v-if="listing.status === 'pending'" class="flex justify-end gap-2 pt-2">
					<Button variant="default" size="sm" @click="showApproveDialog = true">
						Approve
					</Button>
					<Button variant="destructive" size="sm" @click="showRejectDialog = true">
						Reject
					</Button>
				</div>
			</div>
		</div>

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
	</Card>
</template>
