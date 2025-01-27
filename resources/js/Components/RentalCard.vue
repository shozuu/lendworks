<script setup>
import { computed, ref } from "vue";
import { Card, CardContent } from "@/components/ui/card";
import { Link, router } from "@inertiajs/vue3";
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
import RentalStatusBadge from "@/Components/RentalStatusBadge.vue";

const props = defineProps({
	rental: {
		type: Object,
		required: true,
	},
});

const showReturnDialog = ref(false);

const showActions = computed(() => ({
	canCancel: [1, 2].includes(props.rental.status.id),
	canPay: props.rental.status.id === 2,
	canReturn: props.rental.status.id === 4 && !props.rental.return_at,
	canReview: props.rental.status.id === 5 && !props.rental.reviews?.length,
	canPickup: props.rental.status.id === 3 && !props.rental.handover_at,
	isRenting: props.rental.status.id === 3 && props.rental.handover_at,
}));

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

// computed property for image URL
const listingImage = computed(() => {
	const image = props.rental?.listing?.images?.[0];
	if (image?.image_path) {
		return `/storage/${image.image_path}`;
	}
	return "/storage/images/listing/default.png";
});

// Add detailedStatus computed property
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
							<RentalStatusBadge :status="rental.status" />
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
						<p>Owner: {{ rental.listing.user.name }}</p>
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
												{{ formatRentalDate(rental.end_date) }}.
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
			<div class="text-muted-foreground space-y-1 text-sm mt-2">
				<p>{{ detailedStatus }}</p>
				<p v-if="rental.status === 'active'">
					Due: {{ formatRentalDate(rental.end_date) }}
				</p>
			</div>
		</CardContent>
	</Card>
</template>
