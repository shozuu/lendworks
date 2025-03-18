<script setup>
import ItemCard from "@/Components/ItemCard.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import Separator from "@/Components/ui/separator/Separator.vue";
import FilterContent from "@/Components/FilterContent.vue";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import {
	Sheet,
	SheetContent,
	SheetHeader,
	SheetTitle,
	SheetTrigger,
} from "@/components/ui/sheet";
import { Button } from "@/components/ui/button";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from "@/components/ui/dialog";
import { SlidersHorizontal } from "lucide-vue-next";

const props = defineProps({
	listings: Object,
	searchTerm: String,
	categories: Array,
	selectedCategory: [String, Number],
	filters: Object,
	priceRanges: Array,
	timeFrames: Array,
});

const isFilterOpen = ref(false);
const isFilterDialogOpen = ref(false);
const mobileFilters = ref({
	minPrice: props.filters.minPrice,
	maxPrice: props.filters.maxPrice,
	priceRange: props.filters.priceRange,
	timeFrame: props.filters.timeFrame,
});

// For desktop filters (immediate application)
const applyFilter = (params) => {
	if (window.innerWidth >= 768) {
		// md breakpoint
		router.get(
			route("explore"),
			{
				...route().params,
				...params,
			},
			{
				preserveState: true,
				preserveScroll: true,
			}
		);
	}
};

// For mobile filters (temporary state)
const updateMobileFilter = (params) => {
	mobileFilters.value = {
		...mobileFilters.value,
		...params,
	};
};

// Apply all mobile filters at once
const applyMobileFilters = () => {
	router.get(
		route("explore"),
		{
			...route().params,
			...mobileFilters.value,
		},
		{
			preserveState: true,
			preserveScroll: true,
		}
	);
	isFilterOpen.value = false; // Close the sheet after applying
};

// Clear mobile filters
const clearMobileFilters = () => {
	mobileFilters.value = {
		minPrice: null,
		maxPrice: null,
		priceRange: null,
		timeFrame: null,
	};
};

const clearAllFilters = () => {
	router.get(
		route("explore"),
		{
			search: props.searchTerm,
		},
		{
			preserveState: true,
			preserveScroll: true,
		}
	);
};

const handleCategoryChange = (categoryId) => {
	router.get(
		route("explore"),
		{
			...route().params,
			category: categoryId,
			search: props.searchTerm,
		},
		{
			preserveState: true,
			preserveScroll: true,
		}
	);
};
</script>

<template>
	<Head title="| Explore" />

	<div class="space-y-8">
		<!-- Header Section -->
		<div>
			<h2 class="text-2xl font-semibold tracking-tight">Explore Tools</h2>
			<p class="text-sm text-muted-foreground mt-1">
				Browse a wide range of tools available for rent near you.
			</p>
			<Separator class="mt-4" />
		</div>

		<!-- Filter Controls Section -->
		<div class="flex items-center gap-4">
			<!-- Category Select - Always Visible -->
			<Select
				:modelValue="selectedCategory ?? null"
				@update:modelValue="handleCategoryChange"
				class="max-w-[180px]"
			>
				<SelectTrigger class="w-[180px]">
					<SelectValue placeholder="All Categories">
						{{
							selectedCategory
								? categories.find((c) => c.id == selectedCategory)?.name
								: "All Categories"
						}}
					</SelectValue>
				</SelectTrigger>
				<SelectContent>
					<SelectItem :value="null">All Categories</SelectItem>
					<SelectItem
						v-for="category in categories"
						:key="category.id"
						:value="category.id"
					>
						{{ category.name }}
					</SelectItem>
				</SelectContent>
			</Select>

			<!-- Mobile Sheet Trigger -->
			<div class="md:hidden flex-1">
				<Sheet v-model:open="isFilterOpen">
					<SheetTrigger asChild>
						<Button variant="outline" class="w-full gap-2">
							<SlidersHorizontal class="h-4 w-4" />
							<span>Filters</span>
						</Button>
					</SheetTrigger>
					<SheetContent side="right" class="w-full sm:w-[380px] flex flex-col">
						<SheetHeader>
							<SheetTitle>Filters</SheetTitle>
						</SheetHeader>

						<!-- Reusable Filter Content -->
						<FilterContent
							:filters="mobileFilters"
							:price-ranges="priceRanges"
							:time-frames="timeFrames"
							:is-mobile="true"
							@update="updateMobileFilter"
						/>

						<!-- Sheet Footer -->
						<div class="sticky bottom-0 border-t bg-background p-4 mt-auto space-y-2">
							<Button @click="applyMobileFilters" class="w-full"> Apply Filters </Button>
							<Button
								v-if="Object.values(mobileFilters).some((v) => v !== null)"
								@click="clearMobileFilters"
								variant="outline"
								class="w-full"
							>
								Clear Filters
							</Button>
						</div>
					</SheetContent>
				</Sheet>
			</div>

			<!-- Desktop Dialog Trigger -->
			<div class="hidden md:block">
				<Dialog v-model:open="isFilterDialogOpen">
					<DialogTrigger asChild>
						<Button variant="outline" class="gap-2">
							<SlidersHorizontal class="h-4 w-4" />
							<span>Filters</span>
						</Button>
					</DialogTrigger>
					<DialogContent class="sm:max-w-[425px]">
						<DialogHeader>
							<DialogTitle>Filters</DialogTitle>
						</DialogHeader>

						<!-- Reusable Filter Content -->
						<FilterContent
							:filters="filters"
							:price-ranges="priceRanges"
							:time-frames="timeFrames"
							@update="applyFilter"
						/>

						<!-- Dialog Footer -->
						<div
							v-if="
								filters.minPrice ||
								filters.maxPrice ||
								filters.priceRange ||
								filters.timeFrame
							"
						>
							<Separator />
							<div class="pt-4">
								<Button variant="outline" @click="clearAllFilters" class="w-full">
									Clear all filters
								</Button>
							</div>
						</div>
					</DialogContent>
				</Dialog>
			</div>
		</div>

		<!-- Desktop Layout -->
		<div class="flex flex-col gap-8">
			<!-- Main Content -->
			<div class="flex-1 min-w-0">
				<div v-if="Object.keys(listings.data).length">
					<div
						class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
					>
						<div v-for="listing in listings.data" :key="listing.id">
							<ItemCard :listing="listing" />
						</div>
					</div>

					<div class="mt-8">
						<PaginationLinks :paginator="listings" />
					</div>
				</div>

				<div
					v-else
					class="flex flex-col items-center justify-center py-12 text-center space-y-2"
				>
					<p class="text-lg font-medium">No listings found</p>
					<p class="text-sm text-muted-foreground">
						Try adjusting your search or filter criteria
					</p>
				</div>
			</div>
		</div>
	</div>
</template>
