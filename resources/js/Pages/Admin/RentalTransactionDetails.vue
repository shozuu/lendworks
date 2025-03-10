<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import RentalStatusBadge from "@/Components/RentalStatusBadge.vue";
import { formatNumber, formatDateTime, timeAgo } from "@/lib/formatters";
import { Separator } from "@/components/ui/separator";
import RentalTimeline from "@/Components/RentalTimeline.vue";
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
import {Package, AlertCircle} from "lucide-vue-next";
import { Button } from "@/components/ui/button";

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
            rental_request: props.rental,  // Pass the complete rental context
            total_price: props.rental.total_price
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

							<!-- Price Breakdown -->
							<div class="space-y-4">
								<h4 class="font-medium">Price Details</h4>
								<div class="space-y-2">
									<!-- Regular pricing items -->
									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">
											{{ formatNumber(rental.listing.price) }} × {{ rentalDays }} rental
											days
										</span>
										<span>{{ formatNumber(rental.base_price) }}</span>
									</div>

									<div class="flex justify-between text-sm">
										<span class="text-muted-foreground">Duration Discount</span>
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
										<span>{{ formatNumber(rental.deposit_fee) }}</span>
									</div>

									<Separator class="my-2" />

									<!-- Base total -->
									<div class="flex justify-between font-medium">
										<span>Total Amount</span>
										<span>{{ formatNumber(baseTotal) }}</span>
									</div>

									<!-- Add Overdue Fee section if rental is overdue -->
									<template v-if="rental.is_overdue">
										<div class="mt-4 pt-4 border-t">
											<!-- Overdue Fee -->
											<div class="flex justify-between text-sm text-destructive">
												<span class="font-medium">Overdue Fee</span>
												<span>+ {{ formatNumber(rental.overdue_fee) }}</span>
											</div>

											<Separator class="my-2" />

											<!-- Final total with overdue -->
											<div class="flex justify-between font-medium">
												<span>Total Amount with Overdue Fee</span>
												<span class="text-destructive">{{ formatNumber(totalWithOverdue) }}</span>
											</div>
										</div>
									</template>

									<p class="text-muted-foreground mt-2 text-xs">
										Note: Security deposit will be refunded after the rental period, subject to item condition
									</p>
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
	</div>
</template>

<style scoped>
.shadow-sm {
	box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}
</style>
