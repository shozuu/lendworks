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
import { ref, computed } from "vue";
import { formatLabel } from "@/lib/formatters";

const props = defineProps({
	groupedRentals: Object,
	rentalStats: Object,
	cancellationReasons: Array,
});
console.log(props.groupedRentals);
const selectedTab = ref("pending");

const tabs = [
	{ id: "pending", label: "Pending" },
	{ id: "approved", label: "Approved" },
	{ id: "payments", label: "Payments" },
	{ id: "to_receive", label: "To Receive" },
	{ id: "active", label: "Active" },
	{ id: "completed", label: "Completed" },
	{ id: "rejected", label: "Rejected" },
	{ id: "cancelled", label: "Cancelled" },
];

// Computed property to handle payment-related rentals
const groupedRentals = computed(() => {
	const result = { ...props.groupedRentals };

	// Create payments group if it doesn't exist
	if (!result.payments) {
		result.payments = [];
		// Get all payment-related rentals
		["payment_pending", "payment_rejected"].forEach((status) => {
			if (props.groupedRentals[status]) {
				result.payments.push(...props.groupedRentals[status]);
			}
		});
	}

	// Handle to_receive group (renamed from to_handover)
	if (result.to_handover || result.pending_proof) {
		result.to_receive = [...(result.to_handover || []), ...(result.pending_proof || [])];
		delete result.to_handover;
		delete result.pending_proof;
	}

	return result;
});

const handleValueChange = (value) => {
	selectedTab.value = value;
};
</script>

<template>
	<Head title="| My Rentals" />
	<div class="space-y-6">
		<!-- header -->
		<div class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-4">
			<div class="space-y-1">
				<h2 class="text-2xl font-semibold tracking-tight">My Rentals</h2>
				<p class="text-muted-foreground text-sm">Manage your rental requests</p>
			</div>
		</div>

		<!-- status summary cards -->
		<div class="md:grid-cols-3 lg:grid-cols-6 grid grid-cols-2 gap-3">
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
					<div v-if="groupedRentals[tab.id]?.length" class="space-y-4">
						<RentalCard
							v-for="rental in groupedRentals[tab.id]"
							:key="rental.id"
							:rental="rental"
							:cancellationReasons="cancellationReasons"
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
				<div v-if="groupedRentals[selectedTab]?.length" class="space-y-4">
					<RentalCard
						v-for="rental in groupedRentals[selectedTab]"
						:key="rental.id"
						:rental="rental"
						:cancellationReasons="cancellationReasons"
					/>
				</div>
				<div v-else class="text-muted-foreground py-10 text-center">No rentals found</div>
			</div>
		</div>
	</div>
</template>
