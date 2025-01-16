<script setup>
import ListingCard from "@/Components/ListingCard.vue";
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
	{ value: "pending", label: "Pending Approval" },
];

const filterListings = (value) => {
	switch (value) {
		case "available":
			return props.listings.filter((l) => l.status === "approved" && l.is_available);
		case "unavailable":
			return props.listings.filter((l) => l.approved && !l.is_available);
		case "pending":
			return props.listings.filter((l) => l.status !== "approved");
		default:
			return props.listings;
	}
};
</script>

<template>
	<Head title="| My Listings" />
	<div class="space-y-6">
		<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
			<div class="space-y-1">
				<h2 class="text-xl font-semibold tracking-tight sm:text-2xl">My Listings</h2>
				<p class="text-sm text-muted-foreground">Manage your listed items</p>
			</div>

			<Link :href="route('listing.create')" class="shrink-0">
				<Button size="default" class="w-full sm:w-auto">
					<span>Create Listing</span>
				</Button>
			</Link>
		</div>

		<!-- Tabs for md+ screens -->
		<div class="hidden md:block">
			<Tabs v-model="selectedTab" class="w-full" @update:modelValue="handleValueChange">
				<TabsList class="w-full justify-start">
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
						No listings found in this category.
					</div>
				</TabsContent>
			</Tabs>
		</div>

		<!-- Select for sm screens -->
		<div class="md:hidden">
			<Select v-model="selectedTab" @update:modelValue="handleValueChange">
				<SelectTrigger class="w-full">
					<SelectValue :placeholder="tabs.find((t) => t.value === selectedTab)?.label" />
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
					No listings found in this category.
				</div>
			</div>
		</div>
	</div>
</template>
