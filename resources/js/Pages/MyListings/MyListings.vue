<script setup>
import ListingCard from "@/Components/ListingCard.vue";
import StatCard from "@/Components/StatCard.vue";
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
import { formatLabel } from "@/lib/formatters";

const props = defineProps({
	listings: Array,
	listingStats: Object,
});

const form = useForm({});
const selectedTab = ref("all");

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
</script>

<template>
	<Head title="| My Listings" />
	<div class="space-y-6">
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

			<!-- Tabs for lg+ screens -->
			<div class="lg:block hidden">
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

			<!-- Select for md screens -->
			<div class="lg:hidden">
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
	</div>
</template>
