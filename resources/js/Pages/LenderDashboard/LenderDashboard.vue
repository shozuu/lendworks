<script setup>
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import StatCard from "@/Components/StatCard.vue";
import LenderListingCard from "@/Components/LenderListingCard.vue";
import { ref } from "vue";
import { formatLabel } from "@/lib/formatters";

const props = defineProps({
	groupedListings: Object,
	rentalStats: Object,
});

const selectedTab = ref("pending_requests");

const tabs = [
	{ id: "pending_requests", label: "Pending Requests" },
	{ id: "to_handover", label: "To Handover" },
	{ id: "active_rentals", label: "Active Rentals" },
	{ id: "pending_returns", label: "Pending Returns" },
	{ id: "completed", label: "Completed" },
];

const handleValueChange = (value) => {
	selectedTab.value = value;
};
</script>

<template>
	<Head title="| Lender Dashboard" />
	<div class="space-y-6">
		<!-- Header -->
		<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
			<div class="space-y-1">
				<h2 class="text-2xl font-semibold tracking-tight">Lender Dashboard</h2>
				<p class="text-sm text-muted-foreground">Manage rentals of your listed items</p>
			</div>
		</div>

		<!-- Stats Cards -->
		<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
			<StatCard
				v-for="(count, status) in rentalStats"
				:key="status"
				:label="formatLabel(status)"
				:value="count"
			/>
		</div>

		<!-- Tabs for lg+ screens -->
		<div class="hidden lg:block">
			<Tabs v-model="selectedTab" class="w-full" @update:modelValue="handleValueChange">
				<TabsList class="w-full justify-start">
					<TabsTrigger v-for="tab in tabs" :key="tab.id" :value="tab.id">
						{{ tab.label }}
					</TabsTrigger>
				</TabsList>

				<TabsContent v-for="tab in tabs" :key="tab.id" :value="tab.id">
					<div v-if="groupedListings[tab.id]?.length" class="space-y-4">
						<LenderListingCard
							v-for="listing in groupedListings[tab.id]"
							:key="listing.id"
							:listing="listing"
							:selected-status="tab.id"
						/>
					</div>
					<div v-else class="text-muted-foreground py-10 text-center">
						No listings found
					</div>
				</TabsContent>
			</Tabs>
		</div>

		<!-- Select dropdown for md screens -->
		<div class="lg:hidden">
			<Select v-model="selectedTab" @update:modelValue="handleValueChange">
				<SelectTrigger class="w-full">
					<SelectValue :placeholder="tabs.find((t) => t.id === selectedTab)?.label" />
				</SelectTrigger>
				<SelectContent>
					<SelectItem v-for="tab in tabs" :key="tab.id" :value="tab.id">
						{{ tab.label }}
					</SelectItem>
				</SelectContent>
			</Select>

			<!-- Mobile content -->
			<div class="mt-4">
				<div v-if="groupedListings[selectedTab]?.length" class="space-y-4">
					<LenderListingCard
						v-for="listing in groupedListings[selectedTab]"
						:key="listing.id"
						:listing="listing"
						:selected-status="selectedTab"
					/>
				</div>
				<div v-else class="text-muted-foreground py-10 text-center">
					No listings found
				</div>
			</div>
		</div>
	</div>
</template>
