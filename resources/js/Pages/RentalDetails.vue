<script setup>
import { Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import RentalStatusBadge from "@/Components/RentalStatusBadge.vue";
import { formatNumber, formatDateTime, timeAgo } from "@/lib/formatters";
import { Separator } from "@/components/ui/separator";
import { Package, Clock } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { computed } from "vue";
import { calculateDiscountPercentage } from "@/lib/rentalCalculator";
import { useForm } from "@inertiajs/vue3";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import { ref } from "vue";
import RentalTimeline from "@/Components/RentalTimeline.vue";
import { Link } from "@inertiajs/vue3";
import PaymentDialog from "@/Components/PaymentDialog.vue";

const props = defineProps({
	rental: Object,
	userRole: String,
	rejectionReasons: Array,
	cancellationReasons: Array,
});

// Computed properties for role-specific content
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

const rentalDays = computed(() => {
	const start = new Date(props.rental.start_date);
	const end = new Date(props.rental.end_date);

	// Set both dates to start of day in Manila timezone
	start.setHours(0, 0, 0, 0);
	end.setHours(0, 0, 0, 0);

	const diffTime = Math.abs(end.getTime() - start.getTime());

	// Add 1 to include both start and end dates
	return Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;
});

const discountPercentage = computed(() =>
	calculateDiscountPercentage(rentalDays.value, props.rental.listing.value)
);

// Dialog states
const showCancelDialog = ref(false);
const showRejectDialog = ref(false);
const showAcceptDialog = ref(false);

// Forms
const approveForm = useForm({});
const rejectForm = useForm({
	rejection_reason_id: "",
	custom_feedback: "",
});
const cancelForm = useForm({
	cancellation_reason_id: "",
	custom_feedback: "",
});

const isOtherReason = computed(() => {
	const rejectionReason = rejectionReasons.value.find(
		(r) => r.value === rejectForm.rejection_reason_id
	);
	const cancellationReason = cancellationReasons.value.find(
		(r) => r.value === cancelForm.cancellation_reason_id
	);

	return (
		rejectionReason?.label === "Other Reason" || cancellationReason?.label === "Other"
	);
});

// Reactive references for select options
const rejectionReasons = computed(() => props.rejectionReasons || []);
const cancellationReasons = computed(() => props.cancellationReasons || []);

// Handle actions
const handleApprove = () => {
	approveForm.patch(route("rental-request.approve", props.rental.id), {
		preserveScroll: true,
		onSuccess: () => {
			showAcceptDialog.value = false;
		},
	});
};

const handleReject = () => {
	rejectForm.patch(route("rental-request.reject", props.rental.id), {
		preserveScroll: true,
		onSuccess: () => {
			showRejectDialog.value = false;
			// Reset form
			rejectForm.rejection_reason_id = "";
			rejectForm.custom_feedback = "";
		},
	});
};

const handleCancel = () => {
	cancelForm.patch(route("rental-request.cancel", props.rental.id), {
		preserveScroll: true,
		onSuccess: () => {
			showCancelDialog.value = false;
			// Reset form
			cancelForm.cancellation_reason_id = "";
			cancelForm.custom_feedback = "";
		},
	});
};

// Add computed property for payment
const payment = computed(() => props.rental.payment_request);

// Add ref for payment dialog
const showPaymentDialog = ref(false);

// list of actions available for the rental as defined in the model
const actions = computed(() => props.rental.available_actions);
</script>

<template>
	<Head title="Rental Details" />

	<div class="space-y-8">
		<!-- Header -->
		<div
			class="sm:flex-row sm:items-center sm:justify-between bg-card flex flex-col gap-4 p-6 border rounded-lg shadow-sm"
		>
			<div class="space-y-2">
				<div class="flex items-center gap-2">
					<Package class="text-muted-foreground w-5 h-5" />
					<h2 class="text-2xl font-semibold tracking-tight">
						Transaction #{{ rental.id }}
					</h2>
				</div>
				<p class="text-muted-foreground text-sm">
					Created {{ timeAgo(rental.created_at) }}
				</p>
			</div>
			<RentalStatusBadge
				:status="rental.status"
				:paymentRequest="rental.payment_request"
				class="sm:text-base self-start text-sm"
			/>
		</div>

		<Card class="shadow-sm">
			<CardHeader class="bg-card border-b">
				<CardTitle>Timeline</CardTitle>
			</CardHeader>
			<CardContent class="p-6">
				<RentalTimeline
					:events="rental.timeline_events"
					:userRole="userRole"
					:rental="rental"
				/>
			</CardContent>
		</Card>

		<!-- Main Content -->
		<div class="md:grid-cols-[2fr_1fr] grid gap-8">
			<!-- Left Column -->
			<div class="space-y-8">
				<!-- Listing Details -->
				<Card class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle>Listing Details</CardTitle>
					</CardHeader>
					<CardContent class="p-6">
						<div class="space-y-6">
							<!-- Item Image and Basic Info -->
							<div class="sm:flex-row flex flex-col gap-4">
								<Link
									:href="route('listing.show', rental.listing.id)"
									class="sm:w-32 sm:h-32 flex-shrink-0 w-full h-48"
								>
									<img
										:src="
											rental.listing.images[0]?.image_path
												? `/storage/${rental.listing.images[0].image_path}`
												: '/storage/images/listing/default.png'
										"
										class="hover:opacity-90 object-cover w-full h-full transition-opacity rounded-lg"
										:alt="rental.listing.title"
									/>
								</Link>
								<div class="space-y-4">
									<div>
										<Link
											:href="route('listing.show', rental.listing.id)"
											class="hover:text-primary transition-colors"
										>
											<h3 class="text-lg font-semibold">{{ rental.listing.title }}</h3>
										</Link>
										<p class="text-muted-foreground text-sm">
											Category: {{ rental.listing.category.name }}
										</p>
									</div>
									<div class="space-y-2">
										<h4 class="font-medium">Meetup Location</h4>
										<div class="space-y-1">
											<p class="text-sm">{{ rental.listing.location.address }}</p>
											<p class="text-muted-foreground text-sm">
												{{ rental.listing.location.city }},
												{{ rental.listing.location.province }}
												{{ rental.listing.location.postal_code }}
											</p>
										</div>
									</div>
								</div>
							</div>

							<Separator />

							<!-- Rental Period -->
							<div class="space-y-4">
								<h4 class="font-medium">Rental Period</h4>
								<div class="grid gap-4">
									<div class="sm:grid-cols-2 grid gap-4">
										<div>
											<p class="text-muted-foreground text-sm">Start Date</p>
											<p class="font-medium">
												{{ formatDateTime(rental.start_date, "MMMM D, YYYY") }}
											</p>
										</div>
										<div>
											<p class="text-muted-foreground text-sm">End Date</p>
											<p class="font-medium">
												{{ formatDateTime(rental.end_date, "MMMM D, YYYY") }}
											</p>
										</div>
									</div>
								</div>
							</div>

							<!-- Price Breakdown -->
							<div class="space-y-4">
								<h4 class="font-medium">Price Details</h4>
								<div class="space-y-2">
									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">
											{{ formatNumber(rental.listing.price) }} × {{ rentalDays }} rental
											days
										</span>
										<span>{{ formatNumber(rental.base_price) }}</span>
									</div>

									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">
											Duration Discount ({{ discountPercentage }}%)
										</span>
										<span>-{{ formatNumber(rental.discount) }}</span>
									</div>

									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">LendWorks Fee</span>
										<span>{{ formatNumber(rental.service_fee) }}</span>
									</div>

									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground"
											>Security Deposit (Refundable)</span
										>
										<span class="text-primary">{{
											formatNumber(rental.deposit_fee)
										}}</span>
									</div>

									<Separator class="my-2" />

									<!-- total with deposit -->
									<div class="flex justify-between font-medium">
										<span>{{
											userRole === "renter" ? "Total Due" : "Total Earnings"
										}}</span>
										<span
											:class="[
												userRole === 'renter' ? 'text-blue-600' : 'text-emerald-600',
											]"
										>
											{{ formatNumber(rental.total_price) }}
										</span>
									</div>

									<p class="text-muted-foreground mt-2 text-xs">
										Note: Security deposit will be refunded after the rental period,
										subject to item condition
									</p>
								</div>
							</div>
						</div>
					</CardContent>
				</Card>
			</div>

			<!-- Right Column -->
			<div class="space-y-8">
				<!-- Actions Card -->
				<Card class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle>Actions</CardTitle>
					</CardHeader>
					<CardContent class="p-6">
						<div class="space-y-4">
							<!-- Payment Actions -->
							<Button
								v-if="actions.canPayNow"
								variant="default"
								class="w-full"
								@click="showPaymentDialog = true"
							>
								Pay Now
							</Button>

							<!-- Cancel Action -->
							<Button
								v-if="actions.canCancel"
								variant="destructive"
								class="w-full"
								@click="showCancelDialog = true"
							>
								Cancel Request
							</Button>

							<!-- Lender Actions -->
							<template v-if="actions.canApprove">
								<Button class="w-full" @click="showAcceptDialog = true">
									Approve Request
								</Button>
								<Button
									variant="destructive"
									class="w-full"
									@click="showRejectDialog = true"
								>
									Reject Request
								</Button>
							</template>

							<!-- No Actions Message -->
							<p
								v-if="!actions.canPayNow && !actions.canCancel && !actions.canApprove"
								class="text-muted-foreground text-sm text-center"
							>
								No actions available at this time.
							</p>
						</div>
					</CardContent>
				</Card>

				<!-- Contact Information -->
				<Card class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle>{{ roleSpecificName.label }} Information</CardTitle>
					</CardHeader>
					<CardContent class="p-6">
						<div class="space-y-4">
							<div>
								<p class="text-muted-foreground text-sm">Name</p>
								<p class="font-medium">{{ roleSpecificName.name }}</p>
							</div>
							<div>
								<p class="text-muted-foreground text-sm">Member Since</p>
								<p class="font-medium">
									{{
										formatDateTime(
											userRole === "renter"
												? rental.listing.user.created_at
												: rental.renter.created_at,
											"MMMM D, YYYY"
										)
									}}
								</p>
							</div>
							<div>
								<p class="text-muted-foreground text-sm">Total Listings</p>
								<p class="font-medium">
									{{
										userRole === "renter"
											? rental.listing.user.listings_count
											: rental.renter.listings_count
									}}
									{{
										userRole === "renter"
											? "items listed"
											: rental.renter.listings_count === 1
											? "item listed"
											: "items listed"
									}}
								</p>
							</div>
							<div class="flex items-center gap-2">
								<Button variant="outline" size="sm" asChild>
									<Link :href="route('listing.show', rental.listing.id)"
										>View Profile</Link
									>
								</Button>
							</div>
						</div>
					</CardContent>
				</Card>
			</div>
		</div>
	</div>

	<!-- Accept Dialog -->
	<ConfirmDialog
		:show="showAcceptDialog"
		title="Approve Rental Request"
		description="Are you sure you want to approve this rental request? This will mark your item as rented and reject all other pending requests."
		confirmLabel="Approve Request"
		confirmVariant="default"
		:processing="approveForm.processing"
		@update:show="showAcceptDialog = $event"
		@confirm="handleApprove"
		@cancel="showAcceptDialog = false"
	/>

	<!-- Reject Dialog -->
	<ConfirmDialog
		:show="showRejectDialog"
		title="Reject Rental Request"
		description="Please select a reason for rejecting this rental request."
		confirmLabel="Reject Request"
		confirmVariant="destructive"
		:processing="rejectForm.processing"
		:disabled="
			!rejectForm.rejection_reason_id || (isOtherReason && !rejectForm.custom_feedback)
		"
		showSelect
		:selectOptions="rejectionReasons"
		:selectValue="rejectForm.rejection_reason_id"
		:showTextarea="isOtherReason"
		:textareaValue="rejectForm.custom_feedback"
		:textareaRequired="isOtherReason"
		textareaPlaceholder="Please provide specific details about why you are rejecting this rental request..."
		@update:show="showRejectDialog = $event"
		@update:selectValue="rejectForm.rejection_reason_id = $event"
		@update:textareaValue="rejectForm.custom_feedback = $event"
		@confirm="handleReject"
		@cancel="showRejectDialog = false"
	/>

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
	<PaymentDialog 
		v-model:show="showPaymentDialog" 
		:rental="rental" 
		:payment="payment" 
		:viewOnly="false"
	/>
</template>
