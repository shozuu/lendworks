<script setup>
import { ref } from "vue";
import { Head } from "@inertiajs/vue3";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import RentalCard from "@/Components/RentalCard.vue";
import { Separator } from "@/components/ui/separator";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import PaginationLinks from "@/Components/PaginationLinks.vue";

const props = defineProps({
	rentals: Object,
	statuses: Object,
});

const currentTab = ref("all");

const filterRentals = (statusGroup) => {
	if (statusGroup === "all") return props.rentals.data;

	return props.rentals.data.filter((rental) => {
		switch (statusGroup) {
			case "pending":
				return rental.rental_status_id === 1;
			case "to_pay":
				return rental.rental_status_id === 2;
			case "to_pickup":
				return rental.rental_status_id === 3;
			case "ongoing":
				return rental.rental_status_id === 4;
			case "completed":
				return rental.rental_status_id === 5;
			case "cancelled":
				return rental.rental_status_id === 6;
			default:
				return true;
		}
	});
};

const rentalFilters = [
	{ id: "all", label: "All Rentals" },
	{ id: "pending", label: "Pending Approval" },
	{ id: "to_pay", label: "To Pay" },
	{ id: "to_pickup", label: "Ready for Pickup" },
	{ id: "ongoing", label: "Currently Renting" },
	{ id: "completed", label: "Completed" },
	{ id: "cancelled", label: "Cancelled" },
];
</script>

<template>
	<Head title="| My Rentals" />

	<div class="space-y-6">
		<div>
			<h2 class="text-2xl font-semibold tracking-tight">My Rentals</h2>
			<p class="text-muted-foreground text-sm">Track and manage your tool rentals</p>
		</div>

		<Separator />

		<!-- Desktop View (lg and up) -->
		<div class="lg:block hidden">
			<Tabs v-model="currentTab" class="w-full">
				<TabsList class="justify-start w-full">
					<TabsTrigger
						v-for="filter in rentalFilters"
						:key="filter.id"
						:value="filter.id"
						class="px-3 text-sm"
					>
						{{ filter.label }}
						{{ statuses[filter.id] ? `(${statuses[filter.id]})` : "" }}
					</TabsTrigger>
				</TabsList>

				<TabsContent :value="currentTab" class="mt-6 space-y-4">
					<div v-if="filterRentals(currentTab).length" class="space-y-6">
						<div class="space-y-4">
							<RentalCard
								v-for="rental in filterRentals(currentTab)"
								:key="rental.id"
								:rental="rental"
							/>
						</div>

						<!-- Pagination -->
						<PaginationLinks v-if="rentals.total > 10" :paginator="rentals" />
					</div>
					<div v-else class="text-muted-foreground py-10 text-center">
						No rentals found
					</div>
				</TabsContent>
			</Tabs>
		</div>

		<!-- Tablet View (md to lg) -->
		<div class="lg:hidden md:block hidden">
			<div class="space-y-6">
				<Select v-model="currentTab">
					<SelectTrigger class="w-full md:w-[260px]">
						<SelectValue :placeholder="'Filter rentals'" />
					</SelectTrigger>
					<SelectContent>
						<SelectItem
							v-for="filter in rentalFilters"
							:key="filter.id"
							:value="filter.id"
						>
							{{ filter.label }}
							{{ statuses[filter.id] ? `(${statuses[filter.id]})` : "" }}
						</SelectItem>
					</SelectContent>
				</Select>

				<div class="mt-6 space-y-6">
					<div v-if="filterRentals(currentTab).length" class="space-y-4">
						<RentalCard
							v-for="rental in filterRentals(currentTab)"
							:key="rental.id"
							:rental="rental"
						/>

						<!-- Pagination for mobile -->
						<PaginationLinks v-if="rentals.total > 10" :paginator="rentals" />
					</div>
					<div v-else class="text-muted-foreground py-10 text-center">
						No rentals found
					</div>
				</div>
			</div>
		</div>

		<!-- Mobile View -->
		<div class="md:hidden space-y-4">
			<Select v-model="currentTab">
				<SelectTrigger class="w-full">
					<SelectValue :placeholder="'Filter rentals'" />
				</SelectTrigger>
				<SelectContent>
					<SelectItem v-for="filter in rentalFilters" :key="filter.id" :value="filter.id">
						{{ filter.label }}
						{{ statuses[filter.id] ? `(${statuses[filter.id]})` : "" }}
					</SelectItem>
				</SelectContent>
			</Select>

			<div class="mt-6 space-y-6">
				<div v-if="filterRentals(currentTab).length" class="space-y-4">
					<RentalCard
						v-for="rental in filterRentals(currentTab)"
						:key="rental.id"
						:rental="rental"
					/>

					<!-- Pagination for mobile -->
					<PaginationLinks v-if="rentals.total > 10" :paginator="rentals" />
				</div>
				<div v-else class="text-muted-foreground py-10 text-center">No rentals found</div>
			</div>
		</div>
	</div>
</template>
