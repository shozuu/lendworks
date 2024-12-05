<script setup>
import { ref, watch, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import ListingCard from "@/Components/ListingCard.vue";
import { Separator } from "@/components/ui/separator";
import BookingCard from "@/Components/BookingCard.vue";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import { router } from "@inertiajs/vue3";
import PaginationLinks from "@/Components/PaginationLinks.vue"; // Add this import

const props = defineProps({
	listings: Object,
	rentals: Object,
	rentalStats: Object,
	listingStats: Object,
});

const views = [
	{ id: "listings", label: "My Listings" },
	{ id: "bookings", label: "Booking Requests" },
];

const listingFilters = [
	{ id: "all", label: "All Listings" },
	{ id: "available", label: "Available" },
	{ id: "rented", label: "Rented" },
];

const bookingFilters = [
	{ id: "to_approve", label: "New Requests" },
	{ id: "to_handover", label: "Ready for Handover" },
	{ id: "active", label: "Currently Rented" },
	{ id: "completed", label: "Completed" },
];

const getCurrentFilters = computed(() => {
	return currentView.value === "listings" ? listingFilters : bookingFilters;
});

const currentView = ref("listings");
const currentTab = ref("all");

watch(currentView, (newView) => {
	currentTab.value = newView === "listings" ? "all" : "to_approve";
});

const filterListings = (filter) => {
    if (!props.listings?.data) return [];
    if (filter === "all") return props.listings.data;

    return props.listings.data.filter((listing) => {
        switch (filter) {
            case "available":
                return listing.approved && listing.is_available && !listing.is_currently_rented;
            case "rented":
                return listing.is_currently_rented > 0;
            default:
                return true;
        }
    });
};

const filterRentals = (filter) => {
    if (!props.rentals?.data) return [];

    return props.rentals.data.filter((rental) => {
        switch (filter) {
            case "to_approve":
                return rental.rental_status_id === 1;  // pending approval
            case "to_handover":
                return rental.rental_status_id === 3;  // approved and paid
            case "active":
                return rental.rental_status_id === 4;  // ongoing rentals
            case "completed":
                return rental.rental_status_id === 5;  // completed rentals
            default:
                return true;
        }
    });
};

const formatTabLabel = (status) => {
	const labels = {
		to_approve: "To Approve",
		to_handover: "To Hand Over",
		active: "Active",
		to_receive: "To Receive",
		completed: "Completed",
		cancelled: "Cancelled",
	};
	return labels[status] || status;
};

const handleToggleAvailability = (listingId) => {
	router.patch(route("my-listings.toggle-availability", listingId));
};
</script>

<template>
	<Head title="| My Listings" />

	<div class="space-y-6">
		<div>
			<h2 class="text-2xl font-semibold tracking-tight">My Listings</h2>
			<p class="text-muted-foreground text-sm">
				Manage your tool listings and track their rental status
			</p>
		</div>

		<Separator />

		<!-- Desktop View (lg and up) -->
		<div class="lg:block hidden">
			<Tabs v-model="currentView" class="w-full">
				<TabsList class="w-full">
					<TabsTrigger v-for="view in views" :key="view.id" :value="view.id" class="px-6">
						{{ view.label }}
					</TabsTrigger>
				</TabsList>

				<TabsContent v-for="view in views" :key="view.id" :value="view.id">
					<Tabs v-model="currentTab" class="w-full">
						<TabsList class="justify-start w-full">
							<TabsTrigger
								v-for="filter in getCurrentFilters"
								:key="filter.id"
								:value="filter.id"
								class="px-3 text-sm"
							>
								{{ filter.label }}
								{{
									view === "listings"
										? listingStats[filter.id]
											? `(${listingStats[filter.id]})`
											: ""
										: rentalStats[filter.id]
										? `(${rentalStats[filter.id]})`
										: ""
								}}
							</TabsTrigger>
						</TabsList>

						<TabsContent :value="currentTab" class="mt-6">
							<div v-if="view === 'listings'">
								<div v-if="filterListings(currentTab).length" class="space-y-4">
									<ListingCard
										v-for="listing in filterListings(currentTab)"
										:key="listing.id"
										:listing="listing"
										@toggleAvailability="handleToggleAvailability"
									/>
								</div>
								<div v-else class="text-muted-foreground py-10 text-center">
									No listings found
								</div>
							</div>
							<div v-else>
								<div v-if="filterRentals(currentTab).length" class="space-y-4">
									<BookingCard
										v-for="rental in filterRentals(currentTab)"
										:key="rental.id"
										:rental="rental"
									/>
								</div>
								<div v-else class="text-muted-foreground py-10 text-center">
									No bookings found
								</div>
							</div>

							<!-- Add pagination -->
							<PaginationLinks
								v-if="currentView === 'listings' && props.listings.total > 10"
								:paginator="props.listings"
							/>
							<PaginationLinks
								v-if="currentView === 'bookings' && props.rentals.total > 10"
								:paginator="props.rentals"
							/>
						</TabsContent>
					</Tabs>
				</TabsContent>
			</Tabs>
		</div>

		<!-- Tablet View (md to lg) and Mobile View -->
		<div class="lg:hidden space-y-4">
			<!-- View Selector -->
			<div class="md:w-[260px] w-full">
				<Select v-model="currentView">
					<SelectTrigger>
						<SelectValue
							:placeholder="
								currentView === 'listings' ? 'My Listings' : 'Booking Requests'
							"
						/>
					</SelectTrigger>
					<SelectContent>
						<SelectItem v-for="view in views" :key="view.id" :value="view.id">
							{{ view.label }}
						</SelectItem>
					</SelectContent>
				</Select>
			</div>

			<!-- Filter Selector -->
			<div class="md:w-[260px] w-full">
				<Select v-model="currentTab">
					<SelectTrigger>
						<SelectValue
							:placeholder="
								currentView === 'listings' ? 'Filter listings' : 'Filter bookings'
							"
						/>
					</SelectTrigger>
					<SelectContent>
						<SelectItem
							v-for="filter in getCurrentFilters"
							:key="filter.id"
							:value="filter.id"
						>
							{{ filter.label }}
							{{
								currentView === "listings"
									? listingStats[filter.id]
										? `(${listingStats[filter.id]})`
										: ""
									: rentalStats[filter.id]
									? `(${rentalStats[filter.id]})`
									: ""
							}}
						</SelectItem>
					</SelectContent>
				</Select>
			</div>

			<!-- Content -->
			<div class="space-y-4">
				<template v-if="currentView === 'listings'">
					<div v-if="filterListings(currentTab).length" class="space-y-4">
						<ListingCard
							v-for="listing in filterListings(currentTab)"
							:key="listing.id"
							:listing="listing"
							@toggleAvailability="handleToggleAvailability"
						/>
					</div>
					<div v-else class="text-muted-foreground py-10 text-center">
						No listings found
					</div>
				</template>
				<template v-else>
					<div v-if="filterRentals(currentTab).length" class="space-y-4">
						<BookingCard
							v-for="rental in filterRentals(currentTab)"
							:key="rental.id"
							:rental="rental"
						/>
					</div>
					<div v-else class="text-muted-foreground py-10 text-center">
						No bookings found
					</div>
				</template>

				<!-- Add pagination for mobile/tablet -->
				<PaginationLinks
					v-if="currentView === 'listings' && props.listings.total > 10"
					:paginator="props.listings"
				/>
				<PaginationLinks
					v-if="currentView === 'bookings' && props.rentals.total > 10"
					:paginator="props.rentals"
				/>
			</div>
		</div>
	</div>
</template>
