<script setup>
import { computed, ref } from "vue";
import { Card, CardContent } from "@/components/ui/card";
import { Link, router } from "@inertiajs/vue3";
import { formatNumber, formatRentalDate } from "@/lib/formatters";
import { Button } from "@/components/ui/button";
import { MessageCircle } from "lucide-vue-next";
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
			<div class="flex flex-col sm:flex-row gap-4">
				<!-- thumbnail -->
				<div class="sm:w-32 sm:h-32 w-24 h-24 overflow-hidden rounded-md shrink-0">
					<Link :href="route('listing.show', rental.listing.id)" class="h-full">
						<img
							:src="listingImage"
							:alt="rental.listing.title"
							class="object-cover w-full h-full"
						/>
					</Link>
				</div>

				<!-- Rental Info -->
				<div class="flex-1 flex flex-col gap-3">
					<!-- title and badge -->
					<div class="flex items-start flex-col sm:flex-row justify-between gap-1">
						<Link
							:href="route('listing.show', rental.listing.id)"
							class="hover:underline font-semibold line-clamp-1"
						>
							{{ rental.listing.title }}
						</Link>
						<div>
							<RentalStatusBadge :status="rental.status" />
						</div>
					</div>
					<div class="text-muted-foreground space-y-1 text-sm">
						<p>{{ detailedStatus }}</p>
						<p v-if="rental.status === 'active'">
							Due: {{ formatRentalDate(rental.end_date) }}
						</p>
					</div>

					<!-- rental details -->
					<div class="text-muted-foreground text-sm">
						<div class="flex gap-2">
							<span class="font-medium">Total:</span>
							<span>{{ formatNumber(rental.total_price) }}</span>
						</div>
						<div class="flex gap-2">
							<span class="font-medium">Period:</span>
							<span>
								{{ formatRentalDate(rental.start_date) }} -
								{{ formatRentalDate(rental.end_date) }}
							</span>
						</div>
						<div class="flex gap-2">
							<span class="font-medium">Owner:</span>
							<span>{{ rental.listing.user.name }}</span>
						</div>
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
			</div>
		</CardContent>
	</Card>

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
							You are returning this item before the scheduled end date. The rental period
							ends
							{{ formatRentalDate(rental.end_date) }}.
						</p>
						<p>Are you sure you want to return this item?</p>
					</div>
				</DialogDescription>
			</DialogHeader>
			<DialogFooter>
				<Button variant="outline" @click="showReturnDialog = false">Cancel</Button>
				<Button variant="default" @click="handleReturn"> Confirm Return </Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
