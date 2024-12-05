<script setup>
import { computed, ref } from "vue";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { format } from "date-fns";
import { Link, router } from "@inertiajs/vue3";
import { formatNumber } from "@/lib/formatters";
import { Button } from "@/components/ui/button";
import { Clock, AlertTriangle } from "lucide-vue-next";
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogDescription,
	DialogFooter,
	DialogTrigger,
} from "@/components/ui/dialog";

const props = defineProps({
	rental: {
		type: Object,
		required: true,
	},
});

const statusColors = {
	1: "yellow",     // pending approval
	2: "muted",      // pending payment
	3: "blue",       // ready for handover
	4: "orange",     // active/ongoing
	5: "default",    // completed
	6: "destructive" // cancelled/declined
};

const showActions = computed(() => ({
	canApprove: props.rental.status.id === 1,
	canHandover: props.rental.status.id === 3 && !props.rental.handover_at,
	canConfirmReturn: props.rental.status.id === 4 && props.rental.return_at,
}));

const statusText = computed(() => {
	switch (props.rental.status.id) {
		case 1: return "New rental request";
		case 2: return "Waiting for payment";
		case 3: return "Ready to handover";
		case 4: return props.rental.return_at ? "Return pending confirmation" : "Currently rented out";
		case 5: return "Completed";
		case 6: return "Cancelled";
		default: return props.rental.status.name;
	}
});

const isEarlyReturn = computed(() => {
	return (
		props.rental.status.id === 4 &&
		props.rental.started_at !== null &&
		new Date(props.rental.end_date) > new Date()
	);
});

const dueSoon = computed(() => {
	return (
		props.rental.status.id === 4 &&
		props.rental.started_at !== null &&
		new Date(props.rental.end_date) <= new Date(Date.now() + 24 * 60 * 60 * 1000)
	);
});

const showReturnDialog = ref(false);
const showHandoverDialog = ref(false);
const showConfirmReturnDialog = ref(false);
const showDeclineDialog = ref(false);

const handleApprove = () => {
	router.patch(
		route("rentals.approve", props.rental.id),
		{},
		{
			onSuccess: () => {
				// Optional: Show success message
			},
		}
	);
};

const handleDecline = () => {
	router.patch(
		route("rentals.decline", props.rental.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => {
				showDeclineDialog.value = false;
			},
		}
	);
};

const handleHandover = () => {
	router.post(route("rentals.handover", props.rental.id), {}, {
		preserveScroll: true,
		onSuccess: () => {
			showHandoverDialog.value = false;
		},
		onError: () => {
			// Error handling could be added here
		},
	});
};

const handleReceiveReturn = () => {
	// The route should match the one in web.php for completing a rental
	router.post(
		route("rentals.complete", props.rental.id),
		{},
		{
			preserveScroll: true, // Add this to preserve scroll position
			onSuccess: () => {
				showReturnDialog.value = false;
			},
			onError: () => {
				// Optionally handle errors here
				showReturnDialog.value = false;
			},
		}
	);
};

const handleConfirmReturn = () => {
    router.post(
        route("rentals.complete", props.rental.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showConfirmReturnDialog.value = false;
            },
        }
    );
};

const approveRental = () => {
    router.patch(route('rentals.approve', props.rental.id));
};

const rejectRental = () => {
    router.patch(route('rentals.reject', props.rental.id));
};

const formatStatus = (status) => {
    const labels = {
        1: 'Pending Approval',
        2: 'Approved',
        3: 'Ready for Handover',
        4: 'Active',
        5: 'Completed'
    };
    return labels[status] || status;
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
						<p>Renter: {{ rental.renter.name }}</p>
					</div>

					<!-- Status Indicators -->
					<div v-if="dueSoon" class="text-warning flex items-center gap-2 text-xs">
						<AlertTriangle class="w-4 h-4" />
						<span>Due for return soon</span>
					</div>

					<!-- Action Buttons -->
					<div class="flex flex-wrap gap-2 pt-2">
						<template v-if="showActions.canApprove">
							<Button size="sm" variant="default" @click="handleApprove">Approve</Button>
							<Dialog v-model:open="showDeclineDialog">
								<DialogTrigger asChild>
									<Button size="sm" variant="outline">Decline</Button>
								</DialogTrigger>
								<DialogContent class="sm:max-w-md">
									<DialogHeader>
										<DialogTitle>Decline Rental Request</DialogTitle>
										<DialogDescription>
											<div class="space-y-2">
												<p>Are you sure you want to decline this rental request?</p>
												<p class="text-muted-foreground text-sm">
													This action cannot be undone.
												</p>
											</div>
										</DialogDescription>
									</DialogHeader>
									<DialogFooter>
										<Button variant="outline" @click="showDeclineDialog = false">Cancel</Button>
										<Button variant="destructive" @click="handleDecline">
											Decline Request
										</Button>
									</DialogFooter>
								</DialogContent>
							</Dialog>
						</template>
						<Dialog v-model:open="showHandoverDialog">
							<DialogTrigger asChild v-if="showActions.canHandover">
								<Button size="sm" variant="default">
									Hand Over Tool
								</Button>
							</DialogTrigger>
							<DialogContent class="sm:max-w-md">
								<DialogHeader>
									<DialogTitle>Hand Over Tool</DialogTitle>
									<DialogDescription>
										<div class="space-y-2">
											<p>Confirm that you are handing over the tool to {{ rental.renter.name }}?</p>
											<p class="text-muted-foreground text-sm">
												This will start the rental period from {{ format(new Date(), "MMM d, yyyy") }}
											</p>
										</div>
									</DialogDescription>
								</DialogHeader>
								<DialogFooter>
									<Button variant="outline" @click="showHandoverDialog = false">Cancel</Button>
									<Button variant="default" @click="handleHandover">
										Confirm Handover
									</Button>
								</DialogFooter>
							</DialogContent>
						</Dialog>
						<Button v-if="showActions.canComplete" @click="handleComplete">
							Mark as Returned
						</Button>
						<Dialog v-model:open="showReturnDialog">
							<DialogTrigger asChild v-if="showActions.canReceive">
								<Button size="sm" :variant="isEarlyReturn ? 'outline' : 'default'">
									{{ showActions.isOverdue ? "Receive Overdue Item" : "Receive Back" }}
								</Button>
							</DialogTrigger>
							<DialogContent class="sm:max-w-md">
								<DialogHeader>
									<DialogTitle>Return Item</DialogTitle>
									<DialogDescription>
										<div class="space-y-2">
											<p v-if="isEarlyReturn" class="text-warning">
												The rental period ends
												{{ format(new Date(rental.end_date), "MMM d, yyyy") }}.
											</p>
											<p>
												Confirm that you have received the item back in good condition?
											</p>
										</div>
									</DialogDescription>
								</DialogHeader>
								<DialogFooter>
									<Button variant="outline" @click="showReturnDialog = false"
										>Cancel</Button
									>
									<Button variant="default" @click="handleReceiveReturn">
										Confirm Return
									</Button>
								</DialogFooter>
							</DialogContent>
						</Dialog>
						<Dialog v-model:open="showConfirmReturnDialog">
							<DialogTrigger asChild v-if="showActions.canConfirmReturn">
								<Button size="sm" variant="default">
									Confirm Return
								</Button>
							</DialogTrigger>
							<DialogContent class="sm:max-w-md">
								<DialogHeader>
									<DialogTitle>Confirm Tool Return</DialogTitle>
									<DialogDescription>
										<div class="space-y-2">
											<p>Please confirm that you have received the tool back in good condition.</p>
											<p class="text-muted-foreground text-sm">
												This will complete the rental and release the payment.
											</p>
										</div>
									</DialogDescription>
								</DialogHeader>
								<DialogFooter>
									<Button variant="outline" @click="showConfirmReturnDialog = false">Cancel</Button>
									<Button variant="default" @click="handleConfirmReturn">
										Confirm Return
									</Button>
								</DialogFooter>
							</DialogContent>
						</Dialog>
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
		</CardContent>
	</Card>
</template>
