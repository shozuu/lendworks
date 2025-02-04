<script setup>
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { formatNumber, formatRentalDate, timeAgo } from "@/lib/formatters";
import { Separator } from "@/components/ui/separator";
import RentalStatusBadge from "@/Components/RentalStatusBadge.vue";
import { Button } from "@/components/ui/button";
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
	show: Boolean,
	rental: Object,
	userRole: {
		type: String,
		validator: (value) => ["renter", "lender"].includes(value),
	},
});

defineEmits(["update:show", "cancel", "approve", "reject"]);

// Replace getStatusMessage with status computed property
const statusMessage = computed(() => {
	if (props.userRole === "renter") {
		return getRenterStatusMessage(props.rental);
	}
	return getLenderStatusMessage(props.rental);
});

function getRenterStatusMessage(rental) {
	if (rental.status === "pending") return "Waiting for owner's response";
	if (rental.status === "approved") return "Ready for handover";
	if (rental.status === "active") {
		return rental.return_at ? "Return initiated" : "Item is currently being rented";
	}
	if (rental.status === "rejected" && rental.latest_rejection) {
		return rental.latest_rejection.rejection_reason.description;
	}
	return "";
}

function getLenderStatusMessage(rental) {
	if (rental.status === "pending") return "New rental request awaiting your response";
	if (rental.status === "approved") return "Pending handover to renter";
	if (rental.status === "active") {
		return rental.return_at ? "Return initiated by renter" : "Currently being rented";
	}
	return "";
}

// Add computed properties for action permissions
const canCancel = computed(() => {
	return ["pending", "approved"].includes(props.rental.status);
});

const canApproveOrReject = computed(() => {
	return (
		props.userRole === "lender" &&
		props.rental.status === "pending" &&
		!props.rental.listing.is_rented
	);
});

// Add computed property for role-specific name
const roleSpecificName = computed(() => {
	if (props.userRole === "renter") {
		return {
			label: "Lender",
			name: props.rental.listing.user.name,
		};
	}
	return {
		label: "Renter",
		name: props.rental.renter.name,
	};
});
console.log(props.rental);
// computed property for rejection details
const rejectionDetails = computed(() => {
	if (props.rental.status === "rejected" && props.rental.latest_rejection) {
		const reason = props.rental.latest_rejection.rejection_reason;
		return {
			label: reason.label,
			description: reason.description,
			action_needed: reason.action_needed,
			feedback: props.rental.latest_rejection.custom_feedback,
		};
	}
	return null;
});
</script>

<template>
	<Dialog :open="show" @update:open="$emit('update:show', $event)">
		<DialogContent
			class="sm:max-w-[600px] w-[calc(100%-2rem)] max-h-[calc(100vh-2rem)] overflow-y-auto p-4 sm:p-6 rounded-lg mx-auto my-4"
		>
			<!-- Header -->
			<div class="space-y-4">
				<div class="flex items-center justify-between">
					<div>
						<h2 class="text-lg font-semibold">Rental Details</h2>
						<div class="text-muted-foreground space-y-1 text-sm">
							<p>Request ID: #{{ rental.id }}</p>
							<p>{{ roleSpecificName.label }}: {{ roleSpecificName.name }}</p>
						</div>
					</div>
					<RentalStatusBadge :status="rental.status" />
				</div>

				<!-- rejection details -->
				<div
					v-if="rejectionDetails"
					class="rounded-lg border bg-card text-card-foreground"
				>
					<!-- Header section -->
					<div class="border-b bg-destructive/5 px-6 py-4 rounded-t-lg">
						<div class="">
							<h3 class="font-medium">Request Rejected</h3>
							<p class="text-sm text-destructive">{{ rejectionDetails.label }}</p>
						</div>
					</div>

					<!-- Content section -->
					<div class="px-6 py-4 space-y-4">
						<!-- Reason Description -->
						<div class="space-y-2">
							<h4 class="text-sm font-medium">Reason:</h4>
							<p class="text-sm text-muted-foreground">
								{{ rejectionDetails.description }}
							</p>
						</div>

						<!-- Action Needed (only for renters) -->
						<div v-if="userRole === 'renter'" class="space-y-2">
							<h4 class="text-sm font-medium">What you can do:</h4>
							<p class="text-sm text-muted-foreground">
								{{ rejectionDetails.action_needed }}
							</p>
						</div>

						<!-- Additional Feedback (if provided) -->
						<div v-if="rejectionDetails.feedback" class="space-y-2">
							<h4 class="text-sm font-medium">Additional feedback from lender:</h4>
							<div class="bg-muted/50 rounded-md p-3">
								<p class="text-sm text-muted-foreground italic">
									"{{ rejectionDetails.feedback }}"
								</p>
							</div>
						</div>
					</div>
				</div>

				<!-- detailed message -->
				<p v-else-if="statusMessage" class="text-muted-foreground text-sm">
					{{ statusMessage }}
				</p>
			</div>

			<Separator />

			<!-- Item Details -->
			<div class="space-y-4">
				<h3 class="font-medium">Item Details</h3>
				<div class="sm:flex-row flex flex-col gap-4">
					<!-- Image takes full width on mobile -->
					<img
						:src="
							rental.listing.images[0]?.image_path
								? `/storage/${rental.listing.images[0].image_path}`
								: '/storage/images/listing/default.png'
						"
						class="sm:w-20 sm:h-20 object-cover w-full h-40 rounded-md"
					/>
					<div class="space-y-3">
						<h4 class="font-medium">{{ rental.listing.title }}</h4>
						<div class="text-muted-foreground grid gap-2 text-sm">
							<p>Category: {{ rental.listing.category.name }}</p>
							<p>
								Location: {{ rental.listing.location.address }},
								{{ rental.listing.location.city }}
							</p>
						</div>
						<p class="text-muted-foreground text-sm">
							{{ rental.listing.desc }}
						</p>
					</div>
				</div>
			</div>

			<Separator />

			<!-- Rental Information -->
			<div class="space-y-4">
				<h3 class="font-medium">Rental Information</h3>
				<div class="grid gap-4 text-sm">
					<div class="space-y-1">
						<p class="text-muted-foreground">Rental Period</p>
						<p class="font-medium">
							{{ formatRentalDate(rental.start_date) }} -
							{{ formatRentalDate(rental.end_date) }}
						</p>
						<p class="text-muted-foreground">Request Date</p>
						<p class="font-medium">{{ timeAgo(rental.created_at) }}</p>
					</div>
				</div>
			</div>

			<Separator />

			<!-- Price Breakdown -->
			<div class="space-y-4">
				<h3 class="font-medium">Price Details</h3>
				<div class="space-y-2 text-sm">
					<div class="flex justify-between">
						<span class="text-muted-foreground">Base Price</span>
						<span>{{ formatNumber(rental.base_price) }}</span>
					</div>
					<div class="flex justify-between">
						<span class="text-muted-foreground">Discount</span>
						<span>-{{ formatNumber(rental.discount) }}</span>
					</div>
					<div class="flex justify-between">
						<span class="text-muted-foreground">Service Fee</span>
						<span>{{ formatNumber(rental.service_fee) }}</span>
					</div>
					<Separator />
					<div class="flex justify-between font-medium">
						<span>Total</span>
						<span>{{ formatNumber(rental.total_price) }}</span>
					</div>
				</div>
			</div>

			<!-- Actions -->
			<div class="sm:flex-row flex flex-col-reverse justify-end gap-2 mt-4">
				<Link :href="route('listing.show', rental.listing.id)" class="sm:mr-auto">
					<Button variant="outline" class="sm:w-auto w-full">View Listing</Button>
				</Link>

				<template v-if="canApproveOrReject">
					<Button @click="$emit('approve')" class="sm:w-auto w-full"> Approve </Button>
					<Button variant="destructive" @click="$emit('reject')" class="sm:w-auto w-full">
						Reject
					</Button>
				</template>

				<Button
					v-if="userRole === 'renter' && canCancel"
					variant="destructive"
					@click="$emit('cancel')"
					class="sm:w-auto w-full"
				>
					Cancel Request
				</Button>
			</div>
		</DialogContent>
	</Dialog>
</template>
