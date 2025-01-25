<script setup>
import ListingCard from "@/Components/ListingCard.vue";
import StatCard from "@/Components/StatCard.vue"; // Add this import
import RentalsList from "@/Components/RentalsList.vue"; // Add this import
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import { ref } from "vue";

const props = defineProps({
	listings: Array,
	rentalRequests: Array,
	rentalStats: Object,
	listingStats: Object,
});

const form = useForm({});
const selectedTab = ref("all");

// rental management state
const selectedRentalTab = ref("pending_requests");

const toggleAvailability = (listing) => {
	form.patch(route("listing.toggle-availability", listing.id));
};

const handleValueChange = (value) => {
	selectedTab.value = value;
};

const tabs = [
	{ value: "all", label: "All Listings" },
	{ value: "available", label: "Available" },
	{ value: "unavailable", label: "Unavailable" },
	{ value: "pending", label: "Pending" },
	{ value: "rejected", label: "Rejected" },
	{ value: "taken_down", label: "Taken Down" },
];

// rental tabs configuration
const rentalTabs = [
	{ value: "pending_requests", label: "Pending Requests" },
	{ value: "to_handover", label: "To Handover" },
	{ value: "active_rentals", label: "Active Rentals" },
	{ value: "pending_returns", label: "Pending Returns" },
	{ value: "completed", label: "Completed" },
];

const filterListings = (value) => {
	switch (value) {
		case "available":
			return props.listings.filter((l) => l.status === "approved" && l.is_available);
		case "unavailable":
			return props.listings.filter((l) => l.status === "approved" && !l.is_available);
		case "pending":
			return props.listings.filter((l) => l.status === "pending");
		case "rejected":
			return props.listings.filter((l) => l.status === "rejected");
		case "taken_down":
			return props.listings.filter((l) => l.status === "taken_down");
		default:
			return props.listings;
	}
};

const filterRentalsByStatus = (status) => {
	// Filter from rentalRequests prop directly
	return props.rentalRequests.filter((rental) => {
		if (!rental) return false;

		switch (status) {
			case "pending_requests":
				return rental.status === "pending";
			case "to_handover":
				return rental.status === "approved" && !rental.handover_at;
			case "active_rentals":
				return rental.status === "active" && !rental.return_at;
			case "pending_returns":
				return rental.status === "active" && rental.return_at;
			case "completed":
				return rental.status === "completed";
			default:
				return false;
		}
	});
};

const formatLabel = (status) => {
	// Convert snake_case to Title Case and handle special cases
	return status
		.split("_")
		.map((word) => word.charAt(0).toUpperCase() + word.slice(1))
		.join(" ");
};
</script>

<template>
	<Head title="| My Listings" />
	<div class="space-y-8">
		<!-- Listing Management Section -->
		<div class="space-y-6">
			<div class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-4">
				<div class="space-y-1">
					<h2 class="sm:text-2xl text-xl font-semibold tracking-tight">My Listings</h2>
					<p class="text-muted-foreground text-sm">Manage your listed items</p>
				</div>

				<Link :href="route('listing.create')" class="shrink-0">
					<Button size="default" class="sm:w-auto w-full">
						<span>Create Listing</span>
					</Button>
				</Link>
			</div>

			<!-- Listing Stats -->
			<div class="sm:grid-cols-3 lg:grid-cols-6 grid grid-cols-2 gap-3">
				<StatCard
					v-for="(count, status) in listingStats"
					:key="status"
					:label="formatLabel(status)"
					:value="count"
				/>
			</div>

			<!-- Listing Management Tabs -->
			<div class="md:block hidden">
				<Tabs v-model="selectedTab" class="w-full" @update:modelValue="handleValueChange">
					<TabsList class="justify-start w-full">
						<TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
							{{ tab.label }}
						</TabsTrigger>
					</TabsList>

					<TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value">
						<div v-if="filterListings(tab.value).length" class="space-y-4">
							<ListingCard
								v-for="listing in filterListings(tab.value)"
								:key="listing.id"
								:listing="listing"
								@toggleAvailability="toggleAvailability"
							/>
						</div>
						<div v-else class="text-muted-foreground py-10 text-center">
							You have no listings yet.
						</div>
					</TabsContent>
				</Tabs>
			</div>

			<!-- Select for sm screens -->
			<div class="md:hidden">
				<Select v-model="selectedTab" @update:modelValue="handleValueChange">
					<SelectTrigger class="w-full">
						<SelectValue
							:placeholder="tabs.find((t) => t.value === selectedTab)?.label"
						/>
					</SelectTrigger>
					<SelectContent>
						<SelectItem v-for="tab in tabs" :key="tab.value" :value="tab.value">
							{{ tab.label }}
						</SelectItem>
					</SelectContent>
				</Select>

				<!-- Content for mobile -->
				<div class="mt-4">
					<div v-if="filterListings(selectedTab).length" class="space-y-4">
						<ListingCard
							v-for="listing in filterListings(selectedTab)"
							:key="listing.id"
							:listing="listing"
							@toggleAvailability="toggleAvailability"
						/>
					</div>
					<div v-else class="text-muted-foreground py-10 text-center">
						You have no listings yet.
					</div>
				</div>
			</div>
		</div>

		<!-- Rental Management Section -->
		<div class="space-y-6">
			<div class="space-y-1">
				<h2 class="sm:text-2xl text-xl font-semibold tracking-tight">
					Rental Management
				</h2>
				<p class="text-muted-foreground text-sm">
					Manage rental requests and ongoing rentals
				</p>
			</div>

			<!-- Rental Stats -->
			<div class="sm:grid-cols-3 lg:grid-cols-5 grid grid-cols-2 gap-3">
				<StatCard
					v-for="(count, status) in rentalStats"
					:key="status"
					:label="formatLabel(status)"
					:value="count"
				/>
			</div>

			<!-- Rental Management Tabs -->
			<Tabs v-model="selectedRentalTab" class="w-full">
				<TabsList class="w-full justify-start">
					<TabsTrigger v-for="tab in rentalTabs" :key="tab.value" :value="tab.value">
						{{ tab.label }} ({{ rentalStats[tab.value] }})
					</TabsTrigger>
				</TabsList>

				<TabsContent v-for="tab in rentalTabs" :key="tab.value" :value="tab.value">
					<RentalsList
						:rentals="filterRentalsByStatus(tab.value)"
						:show-pagination="false"
					/>
				</TabsContent>
			</Tabs>
		</div>
	</div>
</template>
