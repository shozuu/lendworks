<script setup>
import { Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import RentalStatusBadge from "@/Components/RentalStatusBadge.vue";
import { formatNumber, formatDateTime, timeAgo } from "@/lib/formatters";
import { Separator } from "@/components/ui/separator";
import { Package, Clock, DollarSign } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { computed } from "vue";
import { calculateDiscountPercentage } from "@/lib/rentalCalculator";
import { useForm } from "@inertiajs/vue3";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import { ref } from "vue";
import RentalTimeline from "@/Components/RentalTimeline.vue";
import { Link } from "@inertiajs/vue3";
import PaymentDialog from "@/Components/PaymentDialog.vue";
import HandoverDialog from "@/Components/HandoverDialog.vue";
import PickupDateSelector from "@/Components/PickupDateSelector.vue";
import RentalDurationTracker from "@/Components/RentalDurationTracker.vue";
import ReturnScheduler from '@/Components/ReturnScheduler.vue';
import ReturnConfirmationDialog from '@/Components/ReturnConfirmationDialog.vue';
import PaymentProofDialog from '@/Components/PaymentProofDialog.vue';

const props = defineProps({
	rental: Object,
	userRole: String,
	rejectionReasons: Array,
	cancellationReasons: Array,
	lenderSchedules: Array,
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
	return Math.floor(diffTime / (1000 * 60 * 60 * 24));
});

const discountPercentage = computed(() =>
	calculateDiscountPercentage(rentalDays.value, props.rental.listing.value)
);

// Dialog states
const showCancelDialog = ref(false);
const showRejectDialog = ref(false);
const showAcceptDialog = ref(false);
const showHandoverDialog = ref(false);
const showReturnDialog = ref(false);
const returnDialogType = ref('submit');

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

// Fix the canShowHandover computed property
const canShowHandover = computed(() => {
    if (actions.value.canHandover) {
        return props.rental.pickup_schedules?.some(schedule => schedule.is_selected);
    }
    return actions.value.canReceive;
});

// Fix the totalWithOverdue computed to properly add numbers
const totalWithOverdue = computed(() => {
  if (!props.rental.is_overdue) return props.rental.total_price;
  
  // Convert to numbers and ensure positive values
  const total = Math.abs(Number(props.rental.total_price));
  const overdueFee = Math.abs(Number(props.rental.overdue_fee));
  
  // Always add the overdue fee to the total
  return total + overdueFee;
});

// Add computed for base total (without overdue)
const baseTotal = computed(() => props.rental.total_price);

const lenderPayment = computed(() => 
    props.rental.completion_payments?.find(p => p.type === 'lender_payment')
);

const depositRefund = computed(() => 
    props.rental.completion_payments?.find(p => p.type === 'deposit_refund')
);

// Add new refs for payment proof dialog
const showPaymentProofDialog = ref(false);
const selectedPayment = ref(null);

// Add showPaymentProof function
const showPaymentProof = (payment) => {
  selectedPayment.value = payment;
  showPaymentProofDialog.value = true;
};

// Add these computed properties after other computed properties
const displayTotal = computed(() => {
  // Base calculation without deposit
  const base = props.rental.base_price - props.rental.discount;
  
  if (props.userRole === 'renter') {
    // For renter: add service fee
    return base + props.rental.service_fee;
  } else {
    // For lender: subtract service fee
    return base - props.rental.service_fee;
  }
});

// Add these new computed properties after displayTotal
const rentalOnlyTotal = computed(() => {
  const base = props.rental.base_price - props.rental.discount;
  return props.userRole === 'renter' 
    ? base + props.rental.service_fee 
    : base - props.rental.service_fee;
});

const totalWithDeposit = computed(() => {
  return rentalOnlyTotal.value + props.rental.deposit_fee;
});

// Add new computed property for lender earnings
const lenderEarnings = computed(() => {
    if (props.userRole !== 'lender') return null;
    
    const basePrice = props.rental.base_price;
    const discount = props.rental.discount;
    const serviceFee = props.rental.service_fee;
    const overdueFee = props.rental.overdue_payment ? props.rental.overdue_fee : 0;
    const hasOverdue = props.rental.overdue_payment !== null;

    return {
        basePrice,
        discount,
        serviceFee,
        overdueFee,
        hasOverdue,
        baseEarnings: basePrice - discount - serviceFee,
        total: basePrice - discount - serviceFee + overdueFee
    };
});

// Add a computed property for showing overdue sections
const showOverdueSection = computed(() => {
    return props.rental.overdue_days > 0 || props.rental.is_overdue || props.rental.overdue_payment;
});
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

		<RentalDurationTracker :rental="rental" />

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
							<div class="space-y-6">
								<h4 class="font-medium">Price Details</h4>
								<div class="space-y-4">
									<!-- Base price -->
									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">
											{{ formatNumber(rental.listing.price) }} × {{ rentalDays }} rental days
										</span>
										<span>{{ formatNumber(rental.base_price) }}</span>
									</div>

									<!-- Duration Discount -->
									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">
											Duration Discount ({{ discountPercentage }}%)
										</span>
										<span class="text-emerald-500 font-medium">
											-{{ formatNumber(rental.discount) }}
										</span>
									</div>

									<!-- LendWorks Fee -->
									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">LendWorks Fee</span>
										<span :class="{
											'text-emerald-500 font-medium': userRole === 'renter',
											'text-red-500 font-medium': userRole === 'lender'
										}">
											{{ userRole === 'lender' ? '-' : '' }}{{ formatNumber(rental.service_fee) }}
										</span>
									</div>

									<Separator class="my-4" />

									<!-- Rental Only Total -->
									<div class="flex justify-between font-medium pb-2">
										<span>{{ userRole === 'renter' ? 'Rental Amount' : 'Total Earnings' }}</span>
										<span class="text-primary">{{ formatNumber(rentalOnlyTotal) }}</span>
									</div>

									<!-- Security Deposit section -->
									<template v-if="userRole === 'renter'">
										<div class="mt-6 pt-4 border-t space-y-4">
											<div class="flex justify-between text-sm">
												<span class="text-muted-foreground">Security Deposit (Refundable)</span>
												<span class="text-primary">{{ formatNumber(rental.deposit_fee) }}</span>
											</div>

											<div class="flex justify-between font-medium mt-2">
												<span>Total Payment Required</span>
												<span class="text-primary">{{ formatNumber(totalWithDeposit) }}</span>
											</div>
										</div>
									</template>
									<template v-else>
										<p class="text-muted-foreground mt-4 text-xs">
											- Security deposit ({{ formatNumber(rental.deposit_fee) }}) is not included in your earnings and will be refunded to the renter.
										</p>
									</template>

									<!-- Overdue section -->
									<template v-if="rental.overdue_days > 0">
										<div class="flex justify-between text-sm text-destructive font-medium">
											<span>Overdue Fee</span>
											<span>+ {{ formatNumber(rental.overdue_fee) }}</span>
										</div>
										<p class="text-xs text-muted-foreground">
											See Overdue Status Details section for complete breakdown
										</p>
									</template>

									<p class="text-muted-foreground mt-6 text-xs">
										- Security deposit will be refunded after the rental period,
										subject to item condition
									</p>
								</div>
							</div>
						</div>
					</CardContent>
				</Card>

				<!-- After the Listing Details Card and before the Pickup schedule input -->
				<Card v-if="showOverdueSection" class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle class="flex items-center gap-2">
							<Clock class="w-4 h-4 text-destructive" />
							Overdue Status Details
						</CardTitle>
					</CardHeader>
					<CardContent class="p-6">
						<div class="space-y-6">
							<!-- Overdue Period Info -->
							<div class="space-y-4">
								<div class="grid sm:grid-cols-2 gap-4">
									<div>
										<p class="text-muted-foreground text-sm">Original End Date</p>
										<p class="font-medium">
											{{ formatDateTime(rental.end_date, "MMMM D, YYYY") }}
										</p>
									</div>
									<div>
										<p class="text-muted-foreground text-sm">Days Overdue</p>
										<p class="font-medium text-destructive">
											{{ rental.overdue_days }} days
										</p>
									</div>
								</div>
							</div>

							<Separator />

							<!-- Fee Breakdown -->
							<div class="space-y-4">
								<h4 class="font-medium">Fee Breakdown</h4>
								<div class="space-y-2">
									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">Daily Rate</span>
										<span>{{ formatNumber(rental.listing.price) }}</span>
									</div>
									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">Total Days Overdue</span>
										<span>× {{ rental.overdue_days }}</span>
									</div>
									<Separator class="my-2" />
									<div class="flex justify-between font-medium">
										<span>Total Overdue Fee</span>
										<span class="text-destructive">{{ formatNumber(rental.overdue_fee) }}</span>
									</div>
								</div>
							</div>

							<!-- Payment Status -->
							<div v-if="rental.overdue_payment" class="mt-4 p-4 bg-muted rounded-lg">
								<h4 class="text-sm font-medium mb-3">Payment Status</h4>
								<div class="space-y-2">
									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">Status</span>
										<span class="text-emerald-500">Verified</span>
									</div>
									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">Verified On</span>
										<span>{{ formatDateTime(rental.overdue_payment.verified_at) }}</span>
									</div>
									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">Reference</span>
										<span>{{ rental.overdue_payment.reference_number }}</span>
									</div>
								</div>
							</div>

							<!-- Warning for unpaid overdue -->
							<div v-else-if="!rental.overdue_payment" class="mt-4 p-4 bg-destructive/10 rounded-lg">
								<p class="text-sm text-destructive">
									 ⚠️ Overdue payment must be settled before proceeding with the return process
								</p>
							</div>
						</div>
					</CardContent>
				</Card>

				<!--Pickup schedule input-->
				<Card class="space-y-8" v-if="rental.status === 'to_handover'">
					<PickupDateSelector 
					:rental="rental"
					:userRole="userRole"
					:lenderSchedules="lenderSchedules"
					/>
				</Card>

				<!-- Add ReturnScheduler after PickupScheduler -->
				<ReturnScheduler 
					v-if="rental.status === 'active' || 
							rental.status === 'pending_return' || 
							rental.status === 'return_scheduled' ||
							rental.status === 'pending_return_confirmation'"
					:rental="rental"
					:userRole="userRole"
					:lenderSchedules="lenderSchedules"
				/>
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

							<!-- Handover Actions -->
							<Button
								v-if="actions.canHandover"
								variant="default"
								class="w-full"
								@click="showHandoverDialog = true"
								:disabled="!canShowHandover"
							>
								Hand Over Item
							</Button>

							<Button
								v-if="actions.canReceive"
								variant="default"
								class="w-full"
								@click="showHandoverDialog = true"
							>
								Confirm Receipt
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

							 <!-- Return Actions -->
							 <Button
								v-if="actions.canSubmitReturn"
								variant="default"
								class="w-full"
								@click="() => {
									returnDialogType = 'submit';
									showReturnDialog = true;
								}"
							>
								Submit Return Proof
							</Button>
		
							<Button
								v-if="actions.canConfirmReturn"
								variant="default"
								class="w-full"
								@click="() => {
									returnDialogType = 'confirm';
									showReturnDialog = true;
								}"
							>
								Confirm Item Receipt
							</Button>
		
							<Button
								v-if="actions.canFinalizeReturn"
								variant="default"
								class="w-full"
								@click="() => {
									returnDialogType = 'finalize';
									showReturnDialog = true;
								}"
							>
								Complete Transaction
							</Button>

							<!-- No Actions Message -->
							<p
								v-if="
									!actions.canPayNow &&
									!actions.canCancel &&
									!actions.canApprove &&
									!actions.canHandover &&
									!actions.canReceive &&
									!actions.canSubmitReturn &&
									!actions.canConfirmReturn &&
									!actions.canFinalizeReturn
								"
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

				 <Card 
					v-if="props.rental.status === 'completed_pending_payments' || 
						props.rental.status === 'completed_with_payments'" 
					class="shadow-sm"
				>
					<CardHeader class="bg-card border-b">
					<CardTitle>Payment Status</CardTitle>
					</CardHeader>
					<CardContent class="p-6">
					<div v-if="userRole === 'lender'" class="space-y-4">
						<h3 class="font-medium">Payment Processing</h3>
						<p class="text-sm text-muted-foreground">
							Your payment is being processed by the admin. You will be notified once the payment has been sent.
						</p>
						<div v-if="lenderPayment" class="mt-4 p-4 bg-muted rounded-lg">
							<p class="text-sm font-medium">Payment Processed</p>
							<p class="text-sm text-muted-foreground mt-1">
								Reference: {{ lenderPayment.reference_number }}
							</p>
							<Button
								variant="outline"
								size="sm"
								class="mt-2"
								@click="showPaymentProof(lenderPayment)"
							>
								View Payment Proof
							</Button>
						</div>
					</div>

					<div v-if="userRole === 'renter'" class="space-y-4">
						<h3 class="font-medium">Deposit Refund Status</h3>
						<p class="text-sm text-muted-foreground">
							Your security deposit refund is being processed. You will be notified once it has been sent.
						</p>
						<div v-if="depositRefund" class="mt-4 p-4 bg-muted rounded-lg">
							<p class="text-sm font-medium">Refund Processed</p>
							<p class="text-sm text-muted-foreground mt-1">
								Reference: {{ depositRefund.reference_number }}
							</p>
							<Button
								variant="outline"
								size="sm"
								class="mt-2"
								@click="showPaymentProof(depositRefund)"
							>
								View Refund Proof
							</Button>
						</div>
					</div>
					</CardContent>
				</Card>

				 <!-- Update the lender earnings card -->
				<Card v-if="userRole === 'lender'" class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle class="flex items-center gap-2">
							<DollarSign class="w-4 h-4 text-emerald-500" />
							Total Lender Earnings
						</CardTitle>
					</CardHeader>
					<CardContent class="p-4">
						<div class="space-y-3">
							<div class="flex justify-between items-center">
								<span class="font-medium">Base Rental:</span>
								<span>{{ formatNumber(rental.base_price) }}</span>
							</div>
							<div class="flex justify-between items-center text-destructive">
								<span class="font-medium">Discounts & Fees:</span>
								<span>- {{ formatNumber(rental.discount + rental.service_fee) }}</span>
							</div>
							<div v-if="rental.overdue_payment" class="flex justify-between items-center text-emerald-500">
								<span class="font-medium">Overdue Fee:</span>
								<span>+ {{ formatNumber(rental.overdue_fee) }}</span>
							</div>
							<Separator />
							<div class="flex justify-between items-center">
								<span class="font-medium">Total Earnings:</span>
								<span class="text-emerald-500 text-lg">
									{{ formatNumber(rental.base_price - rental.discount - rental.service_fee + (rental.overdue_payment ? rental.overdue_fee : 0)) }}
								</span>
							</div>
							<p class="text-muted-foreground text-xs">
								{{ rental.overdue_payment ? 'Total earnings including overdue fees' : 'Total earnings after discounts and fees' }}
							</p>
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

	<!-- Handover Dialog -->
	<HandoverDialog
		v-model:show="showHandoverDialog"
		:rental="rental"
		:type="actions.canHandover ? 'handover' : 'receive'"
	/>

	<!-- Return Confirmation Dialog -->
	<ReturnConfirmationDialog
		v-model:show="showReturnDialog"
		:rental="rental"
		:type="returnDialogType"
	/>

	<!-- Add PaymentProofDialog component -->
	<PaymentProofDialog
		v-if="selectedPayment"
		:show="showPaymentProofDialog"
		:payment="selectedPayment"
		@update:show="showPaymentProofDialog = $event"
	/>
</template>
