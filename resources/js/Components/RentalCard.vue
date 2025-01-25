<script setup>
import { computed, ref } from "vue";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Link, router, useForm } from "@inertiajs/vue3"; // Add useForm here
import { formatNumber, formatRentalDate, timeAgo } from "@/lib/formatters";
import { Button } from "@/components/ui/button";
import { Clock, AlertTriangle, MessageCircle } from "lucide-vue-next";
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from "@/components/ui/dialog";

const props = defineProps({
	rental: {
		type: Object,
		required: true,
	},
});

const showReturnDialog = ref(false);

const showActions = computed(() => ({
	canCancel: ["pending", "approved"].includes(props.rental.status),
	canPay: props.rental.status === "approved",
	canReturn: props.rental.status === "active" && !props.rental.return_at,
	canReview: props.rental.status === "completed" && !props.rental.reviews?.length,
	canPickup: props.rental.status === "approved" && !props.rental.handover_at,
	isRenting: props.rental.status === "active",
}));

const statusInfo = computed(() => {
	switch (props.rental.status) {
		case "pending":
			return {
				label: "Pending",
				variant: "warning",
			};
		case "approved":
			return {
				label: "Approved",
				variant: "info",
			};
		case "active":
			return {
				label: "Active",
				variant: "success",
			};
		case "completed":
			return {
				label: "Completed",
				variant: "default",
			};
		case "rejected":
			return {
				label: "Rejected",
				variant: "destructive",
			};
		case "cancelled":
			return {
				label: "Cancelled",
				variant: "muted",
			};
		default:
			return {
				label: props.rental.status,
				variant: "default",
			};
	}
});

// Move the longer status text to a separate computed property
const detailedStatus = computed(() => {
	switch (props.rental.status) {
		case "pending":
			return "Waiting for owner's approval";
		case "approved":
			return "Ready for handover";
		case "active":
			return props.rental.return_at ? "Return initiated" : "Currently renting";
		case "completed":
			return "Rental completed";
		case "rejected":
			return props.rental.rejection_reason || "Request rejected";
		case "cancelled":
			return "Request cancelled";
		default:
			return props.rental.status;
	}
});

const isEarlyReturn = computed(() => {
	return props.rental.status.id === 3 && new Date(props.rental.end_date) > new Date();
});

const handlePayment = () => {
	router.get(route("rentals.payment", props.rental.id));
};

const handleReturn = () => {
	router.post(
		route("rentals.return", props.rental.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => {
				showReturnDialog.value = false;
			},
			onError: (errors) => {
				showReturnDialog.value = false;
				// You can add error handling here if needed
			},
		}
	);
};

const emit = defineEmits(["approve", "reject"]);

const handleApprove = () => {
	router.patch(
		route("rentals.approve", props.rental.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => {
				// Optional: show success message
			},
		}
	);
};

const rejectForm = useForm({
	rejection_reason: "",
});

const handleReject = () => {
	rejectForm.patch(route("rentals.reject", props.rental.id), {
		preserveScroll: true,
		onSuccess: () => {
			rejectForm.reset();
			showRejectDialog.value = false;
		},
	});
};

const showRejectDialog = ref(false);

// Add a computed property to safely get the owner name
const ownerName = computed(() => {
	return props.rental?.listing?.user?.name ?? "Unknown Owner";
});

// Add a computed property to safely handle the listing image
const listingImage = computed(() => {
	const images = props.rental?.listing?.images;
	return images && images.length > 0
		? images[0].url
		: "/storage/images/listing/default.png";
});
</script>

<template>
	<Card>
		<CardContent class="sm:p-6 p-4">
			<div class="grid gap-4 sm:grid-cols-[1fr_120px]">
				<!-- Rental Info -->
				<div class="space-y-3">
					<div class="space-y-2">
						<Link
							:href="route('listing.show', rental.listing.id)"
							class="hover:underline font-semibold"
						>
							{{ rental.listing.title }}
						</Link>
						<div>
							<Badge :variant="statusInfo.variant">
								{{ statusInfo.label }}
							</Badge>
						</div>
					</div>

					<div class="text-muted-foreground text-sm">
						<p>
							{{ formatRentalDate(rental.start_date) }} -
							{{ formatRentalDate(rental.end_date) }}
						</p>
						<p class="mt-1">Total: {{ formatNumber(rental.total_price) }}</p>
					</div>

					<div class="text-sm">
						<p>Owner: {{ ownerName }}</p>
					</div>

					<!-- Status Indicators -->
					<div
						class="text-muted-foreground flex items-center gap-2 text-xs"
						v-if="rental.hasStarted"
					>
						<Clock class="w-4 h-4" />
						<span v-if="rental.hasEnded">Ended {{ timeAgo(rental.end_date) }}</span>
						<span v-else>Started {{ timeAgo(rental.start_date) }}</span>
					</div>

					<!-- Warning Indicators -->
					<div
						v-if="showActions.needsAttention"
						class="text-warning flex items-center gap-2 text-xs"
					>
						<AlertTriangle class="w-4 h-4" />
						<span>Has ongoing dispute</span>
					</div>

					<!-- Action Buttons -->
					<div
						class="flex flex-wrap gap-2 pt-2"
						v-if="Object.values(showActions).some(Boolean)"
					>
						<Button
							v-if="showActions.canPay"
							size="sm"
							variant="default"
							@click="handlePayment"
						>
							Pay Now
						</Button>

						<Dialog v-model:open="showReturnDialog">
							<DialogTrigger asChild v-if="showActions.canReturn">
								<Button size="sm" :variant="isEarlyReturn ? 'outline' : 'default'">
									Return Item
								</Button>
							</DialogTrigger>
							<DialogContent>
								<DialogHeader>
									<DialogTitle>Return Item</DialogTitle>
									<DialogDescription>
										<div class="space-y-2">
											<p v-if="isEarlyReturn" class="text-warning">
												You are returning this item before the scheduled end date. The
												rental period ends
												{{ formatDate(rental.end_date) }}.
											</p>
											<p>Are you sure you want to return this item?</p>
										</div>
									</DialogDescription>
								</DialogHeader>
								<DialogFooter>
									<Button variant="outline" @click="showReturnDialog = false"
										>Cancel</Button
									>
									<Button variant="default" @click="handleReturn">
										Confirm Return
									</Button>
								</DialogFooter>
							</DialogContent>
						</Dialog>

						<Button v-if="showActions.canReview" size="sm" variant="outline">
							Leave Review
						</Button>
						<Button v-if="showActions.needsAttention" size="sm" variant="destructive">
							<MessageCircle class="w-4 h-4 mr-2" />
							View Dispute
						</Button>
						<Button v-if="showActions.canPickup" size="sm" disabled>
							Ready for Pickup
						</Button>
						<Button v-if="showActions.canView" size="sm" disabled>
							Currently Renting
						</Button>
					</div>

					<!-- Add approval/rejection buttons for received requests -->
					<div v-if="rental.status === 'pending' && isOwner" class="flex gap-2 mt-4">
						<Button @click="handleApprove">Approve</Button>
						<Button variant="destructive" @click="showRejectDialog = true">Reject</Button>
					</div>

					<!-- Rejection Dialog -->
					<Dialog v-model:open="showRejectDialog">
						<DialogContent>
							<DialogHeader>
								<DialogTitle>Reject Rental Request</DialogTitle>
								<DialogDescription>
									Please provide a reason for rejecting this rental request.
								</DialogDescription>
							</DialogHeader>
							<div class="grid gap-4 py-4">
								<textarea
									v-model="rejectForm.rejection_reason"
									class="min-h-[100px] w-full rounded-md border p-3"
									placeholder="Explain why you're rejecting this request..."
								></textarea>
							</div>
							<DialogFooter>
								<Button variant="outline" @click="showRejectDialog = false"
									>Cancel</Button
								>
								<Button
									variant="destructive"
									@click="handleReject"
									:disabled="rejectForm.processing || !rejectForm.rejection_reason"
								>
									Reject Request
								</Button>
							</DialogFooter>
						</DialogContent>
					</Dialog>
				</div>

				<!-- Listing Image -->
				<div class="sm:order-last aspect-square order-first overflow-hidden rounded-md">
					<img
						:src="listingImage"
						:alt="rental.listing?.title || 'Listing image'"
						class="object-cover w-full h-full"
					/>
				</div>
			</div>
			<!-- Move detailed status to bottom -->
			<div class="text-muted-foreground space-y-1 text-sm mt-2">
				<p>{{ detailedStatus }}</p>
				<p v-if="rental.status === 'active'">
					Due: {{ formatRentalDate(rental.end_date) }}
				</p>
			</div>
		</CardContent>
	</Card>
</template>
