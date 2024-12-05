<script setup>
import { computed, ref } from "vue";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { format, formatDistance } from "date-fns";
import { Link, router } from "@inertiajs/vue3";
import { formatNumber } from "@/lib/formatters";
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

const statusColors = {
	1: "yellow", // pending approval
	2: "blue",   // to pay
	3: "green",  // ready for pickup
	4: "orange", // active/ongoing
	5: "default", // completed
	6: "destructive", // cancelled
};

const showActions = computed(() => ({
	canCancel: [1, 2].includes(props.rental.status.id),
	canPay: props.rental.status.id === 2,
	canReturn: props.rental.status.id === 4 && !props.rental.return_at,
	canReview: props.rental.status.id === 5 && !props.rental.reviews?.length,
	canPickup: props.rental.status.id === 3 && !props.rental.handover_at,
	isRenting: props.rental.status.id === 3 && props.rental.handover_at,
}));

const statusText = computed(() => {
	switch (props.rental.status.id) {
		case 1: return "Waiting for owner's approval";
		case 2: return "Pending payment";
		case 3: return props.rental.handover_at ? "Currently renting" : "Ready for pickup";
		case 4: return props.rental.return_at ? "Return initiated" : "Currently renting";
		case 5: return "Completed";
		case 6: return "Cancelled";
		default: return props.rental.status.name;
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

const timeAgo = (date) => {
	return formatDistance(new Date(date), new Date(), { addSuffix: true });
};
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
							<Badge :variant="statusColors[rental.status.id]">
								{{ rental.status.name }}
							</Badge>
						</div>
					</div>

					<div class="text-muted-foreground text-sm">
						<p>
							{{ format(new Date(rental.start_date), "MMM d, yyyy") }} -
							{{ format(new Date(rental.end_date), "MMM d, yyyy") }}
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
												{{ format(new Date(rental.end_date), "MMM d, yyyy") }}.
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
						:src="rental.listing.images[0]?.url || '/storage/images/listing/default.png'"
						:alt="rental.listing.title"
						class="object-cover w-full h-full"
					/>
				</div>
			</div>
			<div class="text-muted-foreground space-y-1 text-sm">
				<p>{{ statusText }}</p>
				<p v-if="rental.status.id === 4">
					Due: {{ format(new Date(rental.end_date), "MMM d, yyyy") }}
				</p>
			</div>
		</CardContent>
	</Card>
</template>
