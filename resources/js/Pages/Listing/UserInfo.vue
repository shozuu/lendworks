<script setup>
import { Head } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import ItemCard from "@/Components/ItemCard.vue";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { formatDate } from "@/lib/formatters";
import Separator from "@/Components/ui/separator/Separator.vue";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import { ref } from "vue";

// Define props for the user profile and their listings
const props = defineProps({
	user: Object,
	profile: Object,
	listings: Array,
});

const capitalize = (str) => {
	if (!str) return "";
	return str.charAt(0).toUpperCase() + str.slice(1);
};

// Format display values
const displayValue = (value) => value || "Not provided";

// Filter functionality
const selectedTab = ref("all");

const tabs = [
	{ value: "all", label: "All Listings" },
	{ value: "available", label: "Available" },
	{ value: "unavailable", label: "Unavailable" },
];

const handleValueChange = (value) => {
	selectedTab.value = value;
};

const filterListings = (value) => {
	switch (value) {
		case "available":
			return props.listings.filter((l) => l.status === "approved" && l.is_available);
		case "unavailable":
			return props.listings.filter((l) => l.status === "approved" && !l.is_available);
		default:
			return props.listings;
	}
};
</script>

<template>
	<Head :title="`${user.name}'s Profile`" />

	<div class="space-y-8">
		<!-- User Header Section -->
		<Card>
			<CardContent class="p-6">
				<div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
					<Avatar class="w-20 h-20">
						<AvatarImage src="https://picsum.photos/200" />
						<AvatarFallback>{{ user.name.charAt(0) }}</AvatarFallback>
					</Avatar>

					<div>
						<h1 class="text-2xl font-bold text-foreground">{{ user.name }}</h1>
						<p class="text-muted-foreground">
							Member since {{ formatDate(user.created_at) }}
						</p>
					</div>
				</div>
			</CardContent>
		</Card>

		<Card>
			<CardHeader>
				<CardTitle>Verified Information</CardTitle>
			</CardHeader>
			<CardContent>
				<!-- Personal Information -->
				<div class="mb-6">
					<h4 class="text-md font-medium text-foreground mb-2">Personal Details</h4>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<p class="text-sm text-muted-foreground">Full Name</p>
							<p class="font-medium text-foreground">
								{{ profile?.first_name || "Not provided" }}
								{{ profile?.middle_name || "" }} {{ profile?.last_name || "" }}
							</p>
						</div>
						<div>
							<p class="text-sm text-muted-foreground">Birthdate</p>
							<p class="font-medium text-foreground">
								{{ formatDate(profile?.birthdate) }}
							</p>
						</div>
						<div>
							<p class="text-sm text-muted-foreground">Gender</p>
							<p class="font-medium text-foreground">{{ capitalize(profile?.gender) }}</p>
						</div>
						<div>
							<p class="text-sm text-muted-foreground">Civil Status</p>
							<p class="font-medium text-foreground">
								{{ capitalize(profile?.civil_status) }}
							</p>
						</div>
						<div class="grid grid-cols-1 md:grid-cols-1 gap-4">
							<div>
								<p class="text-sm text-muted-foreground">Verified Since</p>
								<p class="font-medium text-foreground">
									{{
										user.id_verified_at ? formatDate(user.id_verified_at) : "Not verified"
									}}
								</p>
							</div>
						</div>
						<div>
							<p class="text-sm text-muted-foreground">Mobile Number</p>
							<p class="font-medium text-foreground">
								{{ displayValue(profile?.mobile_number) }}
							</p>
						</div>
					</div>
				</div>

				<!-- Address Information -->
				<div class="mb-6">
					<h4 class="text-md font-medium text-foreground mb-2">Address Information</h4>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div class="md:col-span-2">
							<p class="text-sm text-muted-foreground">Street Address</p>
							<p class="font-medium text-foreground">
								{{ displayValue(profile?.street_address) }}
							</p>
						</div>
						<div>
							<p class="text-sm text-muted-foreground">Barangay</p>
							<p class="font-medium text-foreground">
								{{ displayValue(profile?.barangay) }}
							</p>
						</div>
						<div>
							<p class="text-sm text-muted-foreground">City</p>
							<p class="font-medium text-foreground">{{ displayValue(profile?.city) }}</p>
						</div>
						<div>
							<p class="text-sm text-muted-foreground">Province</p>
							<p class="font-medium text-foreground">
								{{ displayValue(profile?.province) }}
							</p>
						</div>
					</div>
				</div>
			</CardContent>
		</Card>

		<Separator />

		<!-- Listings Section with Filters -->
		<div>
			<h2 class="text-xl font-semibold mb-4 text-foreground">
				{{ user.name }}'s Listings
			</h2>

			<!-- Tabs for lg+ screens -->
			<div class="lg:block hidden mb-6">
				<Tabs v-model="selectedTab" class="w-full" @update:modelValue="handleValueChange">
					<TabsList class="justify-start">
						<TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
							{{ tab.label }}
						</TabsTrigger>
					</TabsList>
				</Tabs>
			</div>

			<!-- Select for smaller screens -->
			<div class="lg:hidden mb-6">
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
			</div>

			<!-- Filtered listings display -->
			<div
				v-if="filterListings(selectedTab)?.length"
				class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
			>
				<ItemCard
					v-for="listing in filterListings(selectedTab)"
					:key="listing.id"
					:listing="listing"
				/>
			</div>

			<p v-else class="text-muted-foreground">
				This user doesn't have any
				{{ selectedTab !== "all" ? selectedTab : "active" }} listings.
			</p>
		</div>
	</div>
</template>
