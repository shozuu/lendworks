<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import RentalStatusBadge from "@/Components/RentalStatusBadge.vue";
import { formatNumber, formatDateTime, timeAgo } from "@/lib/formatters";
import { Separator } from "@/components/ui/separator";
import RentalTimeline from "@/Components/RentalTimeline.vue";
import { Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { Package, AlertCircle, CheckCircle2, Clock, Wallet, Shield } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import CompletionPaymentDialog from "@/Components/Admin/CompletionPaymentDialog.vue";
import PaymentProofDialog from "@/Components/PaymentProofDialog.vue"; // Add this import
import OverduePaymentDialog from "@/Components/Admin/OverduePaymentDialog.vue"; // Add this import
import RentalDurationTracker from "@/Components/RentalDurationTracker.vue"; // Add this import
import { format } from "date-fns/format";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	rental: Object,
});

// Calculate rental days
const rentalDays = computed(() => {
	const start = new Date(props.rental.start_date);
	const end = new Date(props.rental.end_date);
	start.setHours(0, 0, 0, 0);
	end.setHours(0, 0, 0, 0);
	const diffTime = Math.abs(end.getTime() - start.getTime());
	return Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;
});

// Add a pass-through property for the historical payments in timeline events
const passHistoricalPayment = (event) => {
	if (event.metadata?.payment_request) {
		return {
			...event.metadata.payment_request,
			rental_request: props.rental, // Pass the complete rental context
			total_price: props.rental.total_price,
		};
	}
	return null;
};

// Add computed properties for price calculations
const totalWithOverdue = computed(() => {
	if (!props.rental.is_overdue) return props.rental.total_price;
	const total = Math.abs(Number(props.rental.total_price));
	const overdueFee = Math.abs(Number(props.rental.overdue_fee));
	return total + overdueFee;
});

const baseTotal = computed(() => props.rental.total_price);

// Add new refs
const showLenderPaymentDialog = ref(false);
const showDepositRefundDialog = ref(false);

// Add computed for completion status - Fix the rental reference
const needsCompletionPayments = computed(() => {
	console.log("Rental Status:", props.rental.status);
	console.log("Available Actions:", props.rental.available_actions);

	return (
		props.rental.status === "completed_pending_payments" ||
		props.rental.status === "completed_with_payments"
	);
});

// Fix the lenderPayment computed
const lenderPayment = computed(() =>
	props.rental.completion_payments?.find((p) => p.type === "lender_payment")
);

// Fix the depositRefund computed
const depositRefund = computed(() =>
	props.rental.completion_payments?.find((p) => p.type === "deposit_refund")
);

// Update completion status check to show success state
const showSuccessStatus = computed(() => {
	console.log("Checking Success Status:", {
		status: props.rental.status,
		hasLenderPayment: props.rental.available_actions.hasLenderPayment,
		hasDepositRefund: props.rental.available_actions.hasDepositRefund,
	});

	return (
		props.rental.status === "completed_with_payments" &&
		props.rental.available_actions.hasLenderPayment &&
		props.rental.available_actions.hasDepositRefund
	);
});

// Update/Add these computed properties
const hasOverdueFees = computed(() => {
	// Only show management section if:
	// 1. Rental is overdue but no payment submitted yet, OR
	// 2. Has a pending overdue payment that needs verification
	return (
		props.rental.is_overdue ||
		(props.rental.payment_request?.type === "overdue" &&
			props.rental.payment_request?.status === "pending")
	);
});

const totalLenderEarnings = computed(() => {
	const baseEarnings =
		props.rental.base_price - props.rental.discount - props.rental.service_fee;
	const overdueFees = props.rental.overdue_payment ? props.rental.overdue_fee : 0;
	return baseEarnings + overdueFees;
});

// Add new refs for payment proof dialog
const showPaymentProofDialog = ref(false);
const selectedPayment = ref(null);

// Add showPaymentProof function
const showPaymentProof = (payment) => {
	selectedPayment.value = payment;
	showPaymentProofDialog.value = true;
};

// Add new ref for overdue payment dialog
const showOverduePaymentDialog = ref(false);

// Update the platformEarnings computed property
const platformEarnings = computed(() => {
	// The platform collects service fee from both renter and lender
	const renterServiceFee = props.rental.service_fee; // Fee added to renter's payment
	const lenderServiceFee = props.rental.service_fee; // Fee deducted from lender's earnings
	const totalServiceFee = renterServiceFee + lenderServiceFee;

	return {
		renterFee: renterServiceFee,
		lenderFee: lenderServiceFee,
		total: totalServiceFee,
	};
});

// Add new computed property for lender earnings
const lenderEarnings = computed(() => {
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
		total: basePrice - discount - serviceFee + overdueFee,
	};
});

// Add a computed property for showing overdue sections
const showOverdueSection = computed(() => {
	// Show section if any of these conditions are true:
	// 1. Has overdue days recorded
	// 2. Currently overdue
	// 3. Has overdue payment (verified)
	// 4. Has pending/rejected overdue payment request
	// 5. Transaction was overdue during return process
	return (
		props.rental.overdue_days > 0 ||
		props.rental.is_overdue ||
		props.rental.overdue_payment ||
		props.rental.payment_request?.type === "overdue" ||
		props.rental.status.includes("return")
	);
});
</script>

<template>
	<Head title="| Admin - Rental Transaction Details" />

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
				:payment-request="rental.payment_request"
				class="sm:text-base self-start text-sm"
			/>
		</div>

		<RentalDurationTracker :rental="rental" />

		<!-- Timeline -->
		<Card class="shadow-sm">
			<CardHeader class="bg-card border-b">
				<CardTitle>Timeline</CardTitle>
			</CardHeader>
			<CardContent class="p-6">
				<RentalTimeline
					:events="rental.timeline_events"
					:rental="rental"
					:pass-payment="passHistoricalPayment"
					userRole="admin"
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
									:href="route('admin.listings.show', rental.listing.id)"
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
											:href="route('admin.listings.show', rental.listing.id)"
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

							<!-- Add Pickup Schedule Details -->
							<div
								v-if="rental.pickup_schedules?.find((s) => s.is_selected)"
								class="space-y-4"
							>
								<h4 class="font-medium">Meetup Schedule</h4>
								<div class="bg-muted p-4 rounded-lg">
									<div class="space-y-2">
										<div class="grid gap-2">
											<div class="sm:grid-cols-2 grid gap-4">
												<div>
													<p class="text-muted-foreground text-sm">Date</p>
													<p class="font-medium">
														{{
															formatDateTime(
																rental.pickup_schedules.find((s) => s.is_selected)
																	.pickup_datetime,
																"MMMM D, YYYY"
															)
														}}
													</p>
												</div>
											</div>
											<div class="sm:grid-cols-2 grid gap-4">
												<div>
													<p class="text-muted-foreground text-sm">Time Window</p>
													<p class="font-medium">
														{{
															format(
																new Date(
																	`2000-01-01 ${
																		rental.pickup_schedules.find((s) => s.is_selected)
																			.start_time
																	}`
																),
																"h:mm a"
															)
														}}
														-
														{{
															format(
																new Date(
																	`2000-01-01 ${
																		rental.pickup_schedules.find((s) => s.is_selected)
																			.end_time
																	}`
																),
																"h:mm a"
															)
														}}
													</p>
												</div>
											</div>
										</div>
										<div class="text-muted-foreground border-t pt-2 mt-2 text-sm">
											<p>Location: {{ rental.listing.location.address }}</p>
											<p>
												{{ rental.listing.location.city }},
												{{ rental.listing.location.province }}
											</p>
										</div>
									</div>
								</div>
							</div>

							<!-- Replace Price Breakdown and Rental Details sections with new layout -->
							<div class="space-y-4">
								<h4 class="font-medium">Rental Summary</h4>
								<div>
									<!-- Quantity Info -->
									<div class="grid gap-4 pb-4 border-b">
										<div class="text-sm">
											<div class="flex justify-between font-medium">
												<span>Quantity</span>
												<span>{{ rental.quantity_approved }} unit(s)</span>
											</div>
											<div
												v-if="rental.quantity_approved < rental.quantity_requested"
												class="mt-1"
											>
												<p class="text-muted-foreground text-xs">
													Owner approved {{ rental.quantity_approved }} out of
													{{ rental.quantity_requested }} requested units
												</p>
											</div>
										</div>

										<div class="text-sm">
											<div class="flex justify-between">
												<span class="text-muted-foreground">Duration</span>
												<span>{{ rentalDays }} days</span>
											</div>
											<div class="flex justify-between">
												<span class="text-muted-foreground">Daily Rate</span>
												<span>{{ formatNumber(rental.listing.price) }} per unit</span>
											</div>
										</div>
									</div>

									<!-- Price Breakdown -->
									<div class="pt-4 space-y-2 text-sm">
										<div class="flex justify-between">
											<span class="text-muted-foreground">
												{{ formatNumber(rental.listing.price) }} ×
												{{ rental.quantity_approved }} unit(s) × {{ rentalDays }} days
											</span>
											<span>{{ formatNumber(rental.base_price) }}</span>
										</div>

										<div class="flex justify-between">
											<span class="text-muted-foreground">Duration Discount</span>
											<span class="text-emerald-500"
												>-{{ formatNumber(rental.discount) }}</span
											>
										</div>

										<div class="flex justify-between">
											<span class="text-muted-foreground">Platform Fee</span>
											<span class="text-emerald-500">{{
												formatNumber(rental.service_fee)
											}}</span>
										</div>

										<div class="flex justify-between">
											<span class="text-muted-foreground">
												Security Deposit ({{
													formatNumber(rental.deposit_fee / rental.quantity_approved)
												}}
												per unit)
											</span>
											<span>{{ formatNumber(rental.deposit_fee) }}</span>
										</div>

										<div v-if="rental.is_overdue" class="pt-2 mt-2 border-t">
											<div class="text-destructive flex justify-between">
												<span class="font-medium">Overdue Fee</span>
												<span>{{ formatNumber(rental.overdue_fee) }}</span>
											</div>
											<div v-if="rental.overdue_payment" class="mt-1">
												<div class="text-emerald-500 flex justify-between text-xs">
													<span>Payment Verified</span>
													<span>{{
														formatDateTime(rental.overdue_payment.verified_at)
													}}</span>
												</div>
											</div>
										</div>

										<Separator class="my-2" />

										<div class="flex justify-between font-medium">
											<span>Total Amount</span>
											<span class="text-primary">{{ formatNumber(baseTotal) }}</span>
										</div>

										<p class="text-muted-foreground mt-2 text-xs">
											Security deposit will be refunded after the rental period, subject
											to item condition
										</p>
									</div>
								</div>
							</div>
						</div>
					</CardContent>
				</Card>

				<!-- Completion Payments -->
				<Card v-if="needsCompletionPayments" class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle>{{
							showSuccessStatus ? "Payment Status" : "Completion Payments"
						}}</CardTitle>
					</CardHeader>
					<CardContent class="p-6">
						<!-- Show this when both payments are completed -->
						<div v-if="showSuccessStatus" class="space-y-6">
							<div class="text-emerald-500 flex items-center justify-center gap-2">
								<CheckCircle2 class="w-6 h-6" />
								<p class="text-lg font-medium">All payments processed successfully</p>
							</div>

							<!-- Lender Payment Details -->
							<div class="bg-muted p-4 space-y-3 rounded-lg">
								<h3 class="font-medium">Lender Payment</h3>
								<div class="space-y-2 text-sm">
									<!-- Add breakdown of lender payment -->
									<div class="pb-2 space-y-1 border-b">
										<div class="flex justify-between">
											<span class="text-muted-foreground">Base Rental Price:</span>
											<span>{{ formatNumber(rental.base_price) }}</span>
										</div>
										<div class="flex justify-between">
											<span class="text-muted-foreground">Duration Discount:</span>
											<span class="text-destructive"
												>- {{ formatNumber(rental.discount) }}</span
											>
										</div>
										<div class="flex justify-between">
											<span class="text-muted-foreground">Platform Fee:</span>
											<span class="text-destructive"
												>- {{ formatNumber(rental.service_fee) }}</span
											>
										</div>
										<div v-if="rental.overdue_payment" class="flex justify-between">
											<span class="text-muted-foreground">Overdue Fees:</span>
											<span class="text-emerald-500"
												>+ {{ formatNumber(rental.overdue_fee) }}</span
											>
										</div>
									</div>
									<div class="flex justify-between font-medium">
										<span>Total Payment:</span>
										<span>{{ formatNumber(lenderPayment.amount) }}</span>
									</div>

									<!-- Rest of the payment details -->
									<div v-if="lenderPayment" class="pt-2 mt-2 border-t">
										<div class="flex justify-between">
											<span class="text-muted-foreground">Reference:</span>
											<span class="font-medium">{{
												lenderPayment.reference_number
											}}</span>
										</div>
										<div class="flex justify-between">
											<span class="text-muted-foreground">Processed on:</span>
											<span class="font-medium">{{
												formatDateTime(lenderPayment.processed_at)
											}}</span>
										</div>
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
							</div>

							<!-- Deposit Refund Details -->
							<div class="bg-muted p-4 space-y-3 rounded-lg">
								<h3 class="font-medium">Security Deposit Refund</h3>
								<div class="space-y-2 text-sm">
									<div class="flex justify-between">
										<span class="text-muted-foreground">Amount:</span>
										<span class="font-medium">{{
											formatNumber(depositRefund.amount)
										}}</span>
									</div>
									<div class="flex justify-between">
										<span class="text-muted-foreground">Reference:</span>
										<span class="font-medium">{{ depositRefund.reference_number }}</span>
									</div>
									<div class="flex justify-between">
										<span class="text-muted-foreground">Processed on:</span>
										<span class="font-medium">{{
											formatDateTime(depositRefund.processed_at)
										}}</span>
									</div>
									<Button
										variant="outline"
										size="sm"
										class="mt-2"
										@click="showPaymentProof(depositRefund)"
									>
										View Payment Proof
									</Button>
								</div>
							</div>
						</div>

						<!-- Show this when payments are still pending -->
						<div v-else class="space-y-6">
							<!-- Lender Payment Section -->
							<div class="space-y-4">
								<h3 class="font-medium">Lender Payment</h3>
								<p class="text-muted-foreground text-sm">
									Process the payment to be sent to the lender
								</p>
								<Button
									@click="showLenderPaymentDialog = true"
									:disabled="!rental.available_actions.canProcessLenderPayment"
								>
									{{
										rental.available_actions.canProcessLenderPayment
											? "Process Lender Payment"
											: "Payment Processed"
									}}
								</Button>
							</div>

							<Separator />

							<!-- Deposit Refund Section -->
							<div class="space-y-4">
								<h3 class="font-medium">Security Deposit Refund</h3>
								<p class="text-muted-foreground text-sm">
									Process the security deposit refund for the renter
								</p>
								<Button
									@click="showDepositRefundDialog = true"
									:disabled="!rental.available_actions.canProcessDepositRefund"
								>
									{{
										rental.available_actions.canProcessDepositRefund
											? "Process Deposit Refund"
											: "Refund Processed"
									}}
								</Button>
							</div>
						</div>
					</CardContent>
				</Card>

				<!-- Overdue Payment Management -->
				<Card v-if="showOverdueSection" class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle class="flex items-center gap-2">
							<Clock class="text-destructive w-4 h-4" />
							Overdue Details
						</CardTitle>
					</CardHeader>
					<CardContent class="p-6">
						<div class="space-y-6">
							<!-- Overdue Period Info -->
							<div class="space-y-4">
								<div class="sm:grid-cols-2 grid gap-4">
									<div>
										<p class="text-muted-foreground text-sm">Original End Date</p>
										<p class="font-medium">
											{{ formatDateTime(rental.end_date, "MMMM D, YYYY") }}
										</p>
									</div>
									<div>
										<p class="text-muted-foreground text-sm">Days Overdue</p>
										<p class="text-destructive font-medium">
											{{ rental.overdue_days }} days
										</p>
									</div>
								</div>
							</div>

							<Separator />

							<!-- Fee Calculation -->
							<div class="space-y-4">
								<h4 class="font-medium">Fee Calculation</h4>
								<div class="space-y-2 text-sm">
									<div class="flex justify-between">
										<span class="text-muted-foreground">Daily Rate:</span>
										<span>{{ formatNumber(rental.listing.price) }}</span>
									</div>
									<div class="flex justify-between">
										<span class="text-muted-foreground">Days Overdue:</span>
										<span>× {{ rental.overdue_days }}</span>
									</div>
									<Separator class="my-2" />
									<div class="flex justify-between font-medium">
										<span>Total Overdue Fee:</span>
										<span class="text-destructive">{{
											formatNumber(rental.overdue_fee)
										}}</span>
									</div>
								</div>
							</div>

							<!-- Payment Status -->
							<div class="space-y-3">
								<!-- Verified Payment -->
								<div v-if="rental.overdue_payment?.verified_at" class="space-y-3">
									<h3 class="text-emerald-500 font-medium">Payment Verified</h3>
									<div class="bg-muted p-4 space-y-2 text-sm rounded-lg">
										<div class="flex justify-between">
											<span class="text-muted-foreground">Amount:</span>
											<span>{{ formatNumber(rental.overdue_payment.amount) }}</span>
										</div>
										<div class="flex justify-between">
											<span class="text-muted-foreground">Verified On:</span>
											<span>{{
												formatDateTime(rental.overdue_payment.verified_at)
											}}</span>
										</div>
										<div class="flex justify-between">
											<span class="text-muted-foreground">Reference:</span>
											<span>{{ rental.overdue_payment.reference_number }}</span>
										</div>
										<Button
											v-if="rental.overdue_payment.proof_path"
											variant="outline"
											size="sm"
											class="w-full mt-2"
											@click="showPaymentProof(rental.overdue_payment)"
										>
											View Payment Proof
										</Button>
									</div>
								</div>

								<!-- Pending Verification -->
								<div
									v-else-if="
										rental.payment_request?.type === 'overdue' &&
										rental.payment_request?.status === 'pending'
									"
									class="space-y-3"
								>
									<h3 class="font-medium text-yellow-500">
										Payment Pending Verification
									</h3>
									<div class="bg-muted p-4 space-y-2 text-sm rounded-lg">
										<div class="flex justify-between">
											<span class="text-muted-foreground">Amount:</span>
											<span>{{ formatNumber(rental.overdue_fee) }}</span>
										</div>
										<div class="flex justify-between">
											<span class="text-muted-foreground">Reference:</span>
											<span>{{ rental.payment_request.reference_number }}</span>
										</div>
										<Button
											variant="default"
											size="sm"
											class="w-full mt-2"
											@click="showOverduePaymentDialog = true"
										>
											Review Payment
										</Button>
									</div>
								</div>

								<!-- Rejected Payment -->
								<div
									v-else-if="
										rental.payment_request?.type === 'overdue' &&
										rental.payment_request?.status === 'rejected'
									"
									class="space-y-3"
								>
									<h3 class="text-destructive font-medium">Payment Rejected</h3>
									<div class="bg-muted p-4 space-y-2 text-sm rounded-lg">
										<div class="flex justify-between">
											<span class="text-muted-foreground">Reference:</span>
											<span>{{ rental.payment_request.reference_number }}</span>
										</div>
										<div class="flex justify-between">
											<span class="text-muted-foreground">Rejected On:</span>
											<span>{{ formatDateTime(rental.payment_request.updated_at) }}</span>
										</div>
										<p class="text-destructive/80 mt-2 text-xs">
											Waiting for renter to submit new payment proof
										</p>
									</div>
								</div>

								<!-- No Payment -->
								<div
									v-else-if="!rental.overdue_payment"
									class="text-muted-foreground bg-muted/30 p-4 text-sm text-center rounded-lg"
								>
									Waiting for renter to submit overdue payment
								</div>
							</div>
						</div>
					</CardContent>
				</Card>
			</div>

			<!-- Right Column -->
			<div class="space-y-8">
				<!-- Renter Information -->
				<Card class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle>Renter Information</CardTitle>
					</CardHeader>
					<CardContent class="p-6">
						<div class="space-y-4">
							<div>
								<p class="text-muted-foreground text-sm">Name</p>
								<p class="font-medium">{{ rental.renter.name }}</p>
							</div>
							<div>
								<p class="text-muted-foreground text-sm">Email</p>
								<p class="font-medium">{{ rental.renter.email }}</p>
							</div>
							<div>
								<p class="text-muted-foreground text-sm">Member Since</p>
								<p class="font-medium">
									{{ formatDateTime(rental.renter.created_at, "MMMM D, YYYY") }}
								</p>
							</div>
							<Button asChild>
								<Link :href="route('admin.users.show', rental.renter.id)">
									View Renter Profile
								</Link>
							</Button>
						</div>
					</CardContent>
				</Card>

				<!-- Lender Information -->
				<Card class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle>Lender Information</CardTitle>
					</CardHeader>
					<CardContent class="p-6">
						<div class="space-y-4">
							<div>
								<p class="text-muted-foreground text-sm">Name</p>
								<p class="font-medium">{{ rental.listing.user.name }}</p>
							</div>
							<div>
								<p class="text-muted-foreground text-sm">Email</p>
								<p class="font-medium">{{ rental.listing.user.email }}</p>
							</div>
							<div>
								<p class="text-muted-foreground text-sm">Member Since</p>
								<p class="font-medium">
									{{ formatDateTime(rental.listing.user.created_at, "MMMM D, YYYY") }}
								</p>
							</div>
							<Button asChild>
								<Link :href="route('admin.users.show', rental.listing.user.id)">
									View Lender Profile
								</Link>
							</Button>
						</div>
					</CardContent>
				</Card>

				<!-- Replace the Security Deposit Status card content -->
				<Card class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle class="flex items-center gap-2">
							<Shield class="w-4 h-4 text-blue-500" />
							Security Deposit Status
						</CardTitle>
					</CardHeader>
					<CardContent class="p-4">
						<div class="space-y-4">
							<!-- Original Amount with Per Unit Breakdown -->
							<div class="space-y-2">
								<div class="flex justify-between items-center">
									<span class="text-sm text-muted-foreground">Original Deposit:</span>
									<span>{{ formatNumber(rental.deposit_status?.original_amount || 0) }}</span>
								</div>
								<div class="text-xs text-muted-foreground flex justify-between">
									<span>Per unit ({{ rental.deposit_status?.quantity }} unit(s)):</span>
									<span>{{ formatNumber(rental.deposit_status?.per_unit_amount || 0) }}</span>
								</div>
							</div>

							<!-- Show Deductions if any -->
							<template v-if="rental.deposit_status?.has_deductions">
								<div class="flex justify-between items-center text-destructive">
									<span class="text-sm">Deduction:</span>
									<span>- {{ formatNumber(rental.deposit_status?.deducted_amount || 0) }}</span>
								</div>
								<div class="text-sm bg-muted p-3 rounded-lg">
									<p class="text-muted-foreground">Deduction Reason:</p>
									<p class="mt-1">{{ rental.deposit_status?.deduction_reason || 'No reason provided' }}</p>
								</div>
							</template>

							<Separator />

							<!-- Remaining Amount -->
							<div class="flex justify-between items-center font-medium">
								<span>Remaining Deposit:</span>
								<span class="text-primary">{{ formatNumber(rental.deposit_status?.remaining_amount || 0) }}</span>
							</div>

							<!-- Refund Status -->
							<div v-if="rental.deposit_status?.is_refunded" 
								class="flex items-center gap-2 text-sm text-emerald-500">
								<CheckCircle2 class="w-4 h-4" />
								<span>Deposit has been refunded</span>
							</div>
						</div>
					</CardContent>
				</Card>

				<!-- Replace the existing lender earnings card with this simpler version -->
				<Card class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle> Total Lender Earnings </CardTitle>
					</CardHeader>
					<CardContent class="p-4">
						<div class="space-y-3">
							<div class="flex items-center justify-between">
								<span class="font-medium">Base Rental:</span>
								<span>{{ formatNumber(rental.base_price) }}</span>
							</div>
							<div class="text-destructive flex items-center justify-between">
								<span class="font-medium">Discounts & Fees:</span>
								<span>- {{ formatNumber(rental.discount + rental.service_fee) }}</span>
							</div>
							<div
								v-if="showOverdueSection"
								class="text-emerald-500 flex items-center justify-between"
							>
								<span class="font-medium">Overdue Fee:</span>
								<span>+ {{ formatNumber(rental.overdue_fee) }}</span>
							</div>
							<Separator />
							<div class="flex items-center justify-between">
								<span class="font-medium">Total Earnings:</span>
								<span class="text-emerald-500 text-lg">
									{{
										formatNumber(
											rental.base_price -
												rental.discount -
												rental.service_fee +
												(showOverdueSection ? rental.overdue_fee : 0)
										)
									}}
								</span>
							</div>
							<p class="text-muted-foreground text-xs">
								{{
									showOverdueSection
										? "Total earnings including overdue fees"
										: "Total earnings after discounts and fees"
								}}
							</p>
						</div>
					</CardContent>
				</Card>

				<!-- Platform Earnings Card - Moved and Restyled -->
				<Card class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle> Platform Earnings </CardTitle>
					</CardHeader>
					<CardContent class="p-4">
						<div class="space-y-3">
							<div class="grid gap-2 text-sm">
								<div class="flex items-center justify-between">
									<span class="text-muted-foreground">Renter Fee:</span>
									<span>{{ formatNumber(platformEarnings.renterFee) }}</span>
								</div>
								<div class="flex items-center justify-between">
									<span class="text-muted-foreground">Lender Fee:</span>
									<span>{{ formatNumber(platformEarnings.lenderFee) }}</span>
								</div>
								<Separator class="my-1" />
								<div class="flex items-center justify-between font-medium">
									<span>Total:</span>
									<span class="text-primary">{{
										formatNumber(platformEarnings.total)
									}}</span>
								</div>
							</div>
							<p class="text-muted-foreground text-xs">
								Total fees collected from both parties
							</p>
						</div>
					</CardContent>
				</Card>

				<!-- Status Details -->
				<Card
					v-if="rental.latest_rejection || rental.latest_cancellation"
					class="border-destructive/20 shadow-sm"
				>
					<CardHeader class="bg-card border-b">
						<CardTitle class="flex items-center gap-2">
							<AlertCircle class="text-destructive w-5 h-5" />
							{{ rental.status === "rejected" ? "Rejection" : "Cancellation" }} Details
						</CardTitle>
					</CardHeader>
					<CardContent class="p-6">
						<div class="space-y-4">
							<div>
								<p class="font-medium">Reason:</p>
								<p class="text-muted-foreground mt-1">
									{{
										rental.status === "rejected"
											? rental.latest_rejection.rejection_reason.label
											: rental.latest_cancellation.cancellation_reason.label
									}}
								</p>
							</div>
							<div
								v-if="
									rental.latest_rejection?.custom_feedback ||
									rental.latest_cancellation?.custom_feedback
								"
							>
								<p class="font-medium">Additional Feedback:</p>
								<p class="text-muted-foreground mt-1 italic">
									"{{
										rental.status === "rejected"
											? rental.latest_rejection.custom_feedback
											: rental.latest_cancellation.custom_feedback
									}}"
								</p>
							</div>
						</div>
					</CardContent>
				</Card>
			</div>
		</div>

		<!-- Add completion payment dialogs -->
		<CompletionPaymentDialog
			v-model:show="showLenderPaymentDialog"
			:rental="rental"
			type="lender_payment"
		/>

		<CompletionPaymentDialog
			v-model:show="showDepositRefundDialog"
			:rental="rental"
			type="deposit_refund"
		/>

		<!-- Add PaymentProofDialog component -->
		<PaymentProofDialog
			v-if="selectedPayment"
			:show="showPaymentProofDialog"
			:payment="selectedPayment"
			@update:show="showPaymentProofDialog = $event"
		/>

		<!-- Add OverduePaymentDialog component -->
		<OverduePaymentDialog
			v-if="rental.payment_request?.type === 'overdue'"
			v-model:show="showOverduePaymentDialog"
			:payment="rental.payment_request"
		/>
	</div>
</template>

<style scoped>
.shadow-sm {
	box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}
</style>
