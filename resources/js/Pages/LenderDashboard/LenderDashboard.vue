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
import { ref, computed } from "vue";
import { formatLabel } from "@/lib/formatters";

const props = defineProps({
	groupedListings: Object,
	rentalStats: Object,
	rejectionReasons: Array,
	cancellationReasons: Array,
});
console.log(props.groupedListings);
const selectedTab = ref("pending");

const tabs = [
	{ id: "pending", label: "Pending" },
	{ id: "approved", label: "Approved" },
	{ id: "payments", label: "Payments" },
	{ id: "to_handover", label: "To Handover" },
	{ id: "active", label: "Active" },
	{ id: "pending_returns", label: "Pending Returns" },
	{ id: "completed", label: "Completed" },
	{ id: "rejected", label: "Rejected" },
	{ id: "cancelled", label: "Cancelled" },
];

const handleValueChange = (value) => {
	selectedTab.value = value;
};

// Computed property to handle payment-related rentals
const groupedListings = computed(() => {
	const result = { ...props.groupedListings };

	// If there are to_handover items, combine them with pending_proof
	if (result.to_handover || result.pending_proof) {
		result.to_handover = [...(result.to_handover || []), ...(result.pending_proof || [])];
		// Remove the pending_proof array
		delete result.pending_proof;
	}

	return result;
});
</script>

<template>
	<Head title="| Lender Dashboard" />
	<div class="space-y-6">
		<!-- Header -->
		<div class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-4">
			<div class="space-y-1">
				<h2 class="text-2xl font-semibold tracking-tight">Lender Dashboard</h2>
				<p class="text-muted-foreground text-sm">Manage rentals of your listed items</p>
			</div>
		</div>

		<!-- Stats Cards -->
		<div class="sm:grid-cols-3 lg:grid-cols-6 grid grid-cols-2 gap-3">
			<StatCard
				v-for="(count, status) in rentalStats"
				:key="status"
				:label="formatLabel(status)"
				:value="count"
			/>
		</div>

		<!-- Tabs for lg+ screens -->
		<div class="lg:block hidden">
			<Tabs v-model="selectedTab" class="w-full" @update:modelValue="handleValueChange">
				<TabsList class="justify-start w-full">
					<TabsTrigger v-for="tab in tabs" :key="tab.id" :value="tab.id">
						{{ tab.label }}
					</TabsTrigger>
				</TabsList>

				<TabsContent v-for="tab in tabs" :key="tab.id" :value="tab.id">
					<div v-if="groupedListings[tab.id]?.length" class="space-y-4">
						<LenderListingCard
							v-for="item in groupedListings[tab.id]"
							:key="`${item.listing.id}-${item.rental_request.id}`"
							:data="item"
							:selected-status="tab.id"
							:rejection-reasons="rejectionReasons"
							:cancellation-reasons="cancellationReasons"
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
						v-for="item in groupedListings[selectedTab]"
						:key="`${item.listing.id}-${item.rental_request.id}`"
						:data="item"
						:selected-status="selectedTab"
						:rejection-reasons="rejectionReasons"
						:cancellation-reasons="cancellationReasons"
					/>
				</div>
				<div v-else class="text-muted-foreground py-10 text-center">
					No listings found
				</div>
			</div>
		</div>
	</div>
</template>
