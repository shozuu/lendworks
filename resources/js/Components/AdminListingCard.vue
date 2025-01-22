<script setup>
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { formatNumber, timeAgo } from "@/lib/formatters";
import { Tags, MapPin, PhilippinePeso, XCircle, Clock, User } from "lucide-vue-next";
import { ref, computed } from "vue";
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

const rejectionDetails = computed(() => {
	if (props.listing.status !== "rejected") return null;

	// If we have latest_rejection data
	if (props.listing.latest_rejection?.rejection_reason) {
		return {
			label: props.listing.latest_rejection.rejection_reason.label,
			description: props.listing.latest_rejection.rejection_reason.description,
			feedback: props.listing.latest_rejection.custom_feedback,
		};
	}

	return null;
});

const takedownDetails = computed(() => {
	if (props.listing.status !== "taken_down") return null;

	if (props.listing.latest_takedown?.takedown_reason) {
		return {
			label: props.listing.latest_takedown.takedown_reason.label,
			description: props.listing.latest_takedown.takedown_reason.description,
			feedback: props.listing.latest_takedown.custom_feedback,
		};
	}

	return null;
});
</script>

<template>
	<Card>
		<!-- rejection reason -->
		<div
			v-if="listing.status === 'rejected' && listing.latest_rejection"
			class="bg-destructive/10 p-3 text-sm"
		>
			<div
				class="flex flex-col items-center gap-2 text-destructive sm:flex-row sm:items-start"
			>
				<div class="flex items-center gap-2">
					<XCircle class="w-4 h-4 shrink-0" />
					<p class="font-medium">Rejection Reason:</p>
				</div>
				{{ listing.latest_rejection.rejection_reason.label }}
			</div>
		</div>

		<!-- takedown reason -->
		<div
			v-if="listing.status === 'taken_down' && listing.latest_takedown"
			class="bg-destructive/10 p-3 text-sm"
		>
			<div
				class="flex flex-col items-center gap-2 text-destructive sm:flex-row sm:items-start"
			>
				<div class="flex items-center gap-2">
					<XCircle class="w-4 h-4 shrink-0" />
					<p class="font-medium">Takedown Reason:</p>
				</div>
				{{ listing.latest_takedown.takedown_reason.label }}
			</div>
		</div>

		<div class="flex flex-col sm:flex-row gap-4 p-4">
			<!-- thumbnail -->
			<div class="sm:w-32 sm:h-32 w-24 h-24 overflow-hidden rounded-md shrink-0">
				<Link :href="route('admin.listings.show', listing.id)" class="h-full">
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

			<!-- content -->
			<div class="flex-1 flex flex-col gap-3">
				<!-- title and badge -->
				<div class="flex items-start flex-col sm:flex-row justify-between gap-1">
					<Link
						:href="route('admin.listings.show', listing.id)"
						class="hover:underline font-semibold text-sm sm:text-base line-clamp-1"
					>
						{{ listing.title }}
					</Link>
					<Badge
						:variant="getStatusBadge(listing).variant"
						class="whitespace-nowrap shrink-0"
					>
						{{ getStatusBadge(listing).label }}
					</Badge>
				</div>

				<!-- details -->
				<div class="text-muted-foreground text-xs sm:text-sm space-y-1.5 flex-1">
					<div class="flex items-center gap-1">
						<Tags class="w-4 h-4 shrink-0" />
						<span class="truncate">{{ listing.category?.name }}</span>
					</div>

					<div class="flex items-center gap-1">
						<PhilippinePeso class="w-4 h-4 shrink-0" />
						<span class="whitespace-nowrap">{{ formatNumber(listing.price) }}/day</span>
					</div>

					<div class="flex items-center gap-1">
						<MapPin class="w-4 h-4 shrink-0" />
						<span class="line-clamp-1">
							{{ listing.location?.address ?? "No location specified" }}
						</span>
					</div>

					<div class="flex items-center gap-1">
						<Clock class="w-4 h-4 shrink-0" />
						<span>Listed {{ timeAgo(listing.created_at) }}</span>
					</div>

					<div class="flex items-center gap-1">
						<User class="w-4 h-4 shrink-0" />
						<Link
							:href="route('admin.users.show', listing.user.id)"
							class="text-primary hover:underline text-sm"
						>
							{{ listing.user.name }}
						</Link>
					</div>
				</div>

				<!-- actions -->
				<div
					v-if="listing.status === 'pending'"
					class="flex flex-wrap gap-2 sm:justify-end"
				>
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
