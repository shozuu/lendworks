<script setup>
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import StatCard from "@/Components/StatCard.vue";
import RentalCard from "@/Components/RentalCard.vue";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import { ref } from "vue";

const props = defineProps({
	rentals: Object,
	stats: Object,
});

const selectedTab = ref("pending");

const tabs = [
	{ id: "pending", label: "Pending" },
	{ id: "approved", label: "Approved" },
	{ id: "active", label: "Active" },
	{ id: "completed", label: "Completed" },
	{ id: "rejected", label: "Rejected" },
	{ id: "cancelled", label: "Cancelled" },
];

const handleValueChange = (value) => {
	selectedTab.value = value;
};
</script>

<template>
	<Head title="| My Rentals" />
	<div class="space-y-6">
		<!-- header -->
		<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
			<div class="space-y-1">
				<h2 class="text-2xl font-semibold tracking-tight">My Rentals</h2>
				<p class="text-sm text-muted-foreground">Manage your rental requests</p>
			</div>
		</div>

		<!-- status summary cards -->
		<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
			<StatCard
				v-for="tab in tabs"
				:key="tab.id"
				:label="tab.label"
				:value="stats[tab.id]"
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
					<div v-if="rentals[tab.id]?.length" class="space-y-4">
						<RentalCard
							v-for="rental in rentals[tab.id]"
							:key="rental.id"
							:rental="rental"
						/>
					</div>
					<div v-else class="text-muted-foreground py-10 text-center">
						No rentals found
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

			<!-- Content for mobile -->
			<div class="mt-4">
				<div v-if="rentals[selectedTab]?.length" class="space-y-4">
					<RentalCard
						v-for="rental in rentals[selectedTab]"
						:key="rental.id"
						:rental="rental"
					/>
				</div>
				<div v-else class="text-muted-foreground py-10 text-center">No rentals found</div>
			</div>
		</div>
	</div>
</template>
