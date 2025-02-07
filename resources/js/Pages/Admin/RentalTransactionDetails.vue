<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import RentalStatusBadge from "@/Components/RentalStatusBadge.vue";
import { formatNumber, formatDateTime, timeAgo } from "@/lib/formatters";
import { Separator } from "@/components/ui/separator";
import {
	CircleDollarSign,
	Calendar,
	User,
	MapPin,
	Package,
	Clock,
	AlertCircle,
} from "lucide-vue-next";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	rental: Object,
});
console.log(props.rental);
</script>

<template>
	<Head title="| Admin - Rental Transaction Details" />

	<div class="space-y-8">
		<!-- header -->
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
				class="sm:text-base self-start text-sm"
			/>
		</div>

		<!-- transaction info -->
		<div class="md:grid-cols-[2fr_1fr] grid gap-8">
			<!-- Left Column -->
			<div class="space-y-8">
				<!-- Listing Details Card -->
				<Card class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle class="flex items-center gap-2">
							<Package class="text-muted-foreground w-5 h-5" />
							Listing Details
						</CardTitle>
					</CardHeader>
					<CardContent class="p-6 space-y-6">
						<!-- Image and Info with better layout -->
						<div class="sm:flex-row flex flex-col gap-6">
							<img
								:src="
									rental.listing.images?.length
										? `/storage/${rental.listing.images[0].image_path}`
										: '/storage/images/listing/default.png'
								"
								:alt="rental.listing.title"
								class="sm:w-32 sm:h-32 object-cover w-full h-48 rounded-lg"
							/>
							<div class="flex-1 space-y-4">
								<div>
									<h3 class="text-lg font-medium">{{ rental.listing.title }}</h3>
									<p class="text-muted-foreground mt-2 text-sm">
										{{ rental.listing.desc }}
									</p>
								</div>

								<div class="grid gap-4">
									<div class="flex items-center gap-2 text-sm">
										<User class="text-muted-foreground shrink-0 w-4 h-4" />
										<span class="font-medium">Owner:</span>
										<Link
											:href="route('admin.users.show', rental.listing.user.id)"
											class="text-primary hover:underline"
										>
											{{ rental.listing.user.name }}
										</Link>
									</div>
									<div class="flex items-start gap-2 text-sm">
										<MapPin class="text-muted-foreground shrink-0 mt-0.5 w-4 h-4" />
										<div>
											<span class="font-medium">Location:</span>
											<p class="text-muted-foreground">
												{{ rental.listing.location?.address }}<br />
												{{ rental.listing.location?.city }},
												{{ rental.listing.location?.province }}
											</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</CardContent>
				</Card>
			</div>

			<!-- Right Column -->
			<div class="space-y-8">
				<!-- Rental Details Card -->
				<Card class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle class="flex items-center gap-2">
							<Clock class="text-muted-foreground w-5 h-5" />
							Rental Information
						</CardTitle>
					</CardHeader>
					<CardContent class="p-6 space-y-6">
						<!-- Renter Info -->
						<div class="pb-6 space-y-4 border-b">
							<h4 class="flex items-center gap-2 font-medium">
								<User class="text-muted-foreground w-4 h-4" />
								Renter Details
							</h4>
							<div class="pl-6 space-y-2">
								<Link
									:href="route('admin.users.show', rental.renter.id)"
									class="text-primary hover:underline block font-medium"
								>
									{{ rental.renter.name }}
								</Link>
								<p class="text-muted-foreground text-sm">{{ rental.renter.email }}</p>
							</div>
						</div>

						<!-- Dates -->
						<div class="pb-6 space-y-4 border-b">
							<h4 class="flex items-center gap-2 font-medium">
								<Calendar class="text-muted-foreground w-4 h-4" />
								Rental Period
							</h4>
							<div class="pl-6 space-y-3 text-sm">
								<div class="grid gap-1">
									<span class="text-muted-foreground">Start Date:</span>
									<span class="font-medium">{{ formatDateTime(rental.start_date) }}</span>
								</div>
								<div class="grid gap-1">
									<span class="text-muted-foreground">End Date:</span>
									<span class="font-medium">{{ formatDateTime(rental.end_date) }}</span>
								</div>
							</div>
						</div>

						<!-- Price Breakdown -->
						<div class="space-y-4">
							<h4 class="flex items-center gap-2 font-medium">
								<CircleDollarSign class="text-muted-foreground w-4 h-4" />
								Price Breakdown
							</h4>
							<div class="bg-muted/50 p-4 space-y-3 text-sm rounded-lg">
								<div class="flex justify-between">
									<span class="text-muted-foreground">Base Price:</span>
									<span>{{ formatNumber(rental.base_price) }}</span>
								</div>
								<div class="flex justify-between">
									<span class="text-muted-foreground">Discount:</span>
									<span class="text-destructive"
										>-{{ formatNumber(rental.discount) }}</span
									>
								</div>
								<div class="flex justify-between">
									<span class="text-muted-foreground">Service Fee:</span>
									<span>{{ formatNumber(rental.service_fee) }}</span>
								</div>
								<div class="flex justify-between">
									<span class="text-muted-foreground"
										>Security Deposit (Refundable):</span
									>
									<span>{{ formatNumber(rental.deposit_fee) }}</span>
								</div>
								<Separator />
								<div class="flex justify-between text-base font-medium">
									<span>Total:</span>
									<span>{{ formatNumber(rental.total_price + rental.deposit_fee) }}</span>
								</div>
							</div>
						</div>
					</CardContent>
				</Card>

				<!-- Current Status Details if applicable -->
				<Card
					v-if="rental.latest_rejection || rental.latest_cancellation"
					class="border-destructive/20 shadow-sm"
				>
					<CardHeader>
						<CardTitle class="text-lg">
							{{ rental.status === "rejected" ? "Rejection" : "Cancellation" }} Details
						</CardTitle>
					</CardHeader>
					<CardContent class="space-y-4">
						<div class="space-y-2">
							<p class="font-medium">
								{{
									rental.status === "rejected"
										? rental.latest_rejection.rejection_reason.label
										: rental.latest_cancellation.cancellation_reason.label
								}}
							</p>
							<p class="text-muted-foreground text-sm">
								{{
									rental.status === "rejected"
										? rental.latest_rejection.rejection_reason.description
										: rental.latest_cancellation.cancellation_reason.description
								}}
							</p>
							<div
								v-if="
									rental.latest_rejection?.custom_feedback ||
									rental.latest_cancellation?.custom_feedback
								"
								class="bg-muted p-3 mt-4 rounded-md"
							>
								<p class="text-muted-foreground text-sm italic">
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
