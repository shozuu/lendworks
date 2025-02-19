<script setup>
import ItemCard from "@/Components/ItemCard.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import Separator from "@/Components/ui/separator/Separator.vue";
import { router } from '@inertiajs/vue3';

const props = defineProps({
	listings: Object,
	searchTerm: String,
	categories: Array,
	selectedCategory: [String, Number],
    filters: Object,
    priceRanges: Array,
    timeFrames: Array,
});

const filterByCategory = (categoryId) => {
	router.get(route('explore'), {
		category: categoryId === props.selectedCategory ? null : categoryId,
		search: props.searchTerm
	}, {
		preserveState: true,
		preserveScroll: true
	});
};

const clearFilter = () => {
    router.get(route('explore'), {
        category: null,
        search: props.searchTerm
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const applyFilter = (params) => {
    router.get(route('explore'), {
        ...route().params,
        ...params
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const clearAllFilters = () => {
    router.get(route('explore'), {
        search: props.searchTerm
    }, {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
	<Head title="| Explore" />

	<div class="space-y-1">
		<h2 class="text-2xl font-semibold tracking-tight">Explore Tools</h2>
		<p class="text-sm text-muted-foreground">
			Browse a wide range of tools available for rent near you.
		</p>
	</div>

	<Separator class="my-4" />

    <div class="flex flex-col gap-6 md:flex-row">
        
        <!-- Main content -->
        <div class="flex-1">
            <!-- Category filters -->
            <div class="mb-6 flex flex-wrap gap-2">
                <button
                    v-for="category in categories"
                    :key="category.id"
                    @click="filterByCategory(category.id)"
                    :class="[
                        'px-3 py-1 rounded-full text-sm inline-flex items-center gap-2',
                        selectedCategory == category.id
                            ? 'bg-primary text-white'
                            : 'bg-muted hover:bg-muted/80'
                    ]"
                >
                    {{ category.name }}
                    <span
                        v-if="selectedCategory == category.id"
                        @click.stop="clearFilter"
                        class="hover:bg-primary-dark rounded-full p-1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </span>
                </button>
            </div>

            <div v-if="Object.keys(listings.data).length">
                <div
                    class="grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4 xl:gap-x-8"
                >
                    <div v-for="listing in listings.data" :key="listing.id">
                        <ItemCard :listing="listing" />
                    </div>
                </div>

                <div class="mt-10">
                    <PaginationLinks :paginator="listings" />
                </div>
            </div>

            <div v-else class="flex justify-center">
                Oops, there's no listing associated with {{ searchTerm }}. Please make sure your
                searches are accurate.
            </div>
        </div>

		<!-- Filters -->
		<div class="w-full md:w-72 space-y-8">
            <div class="p-6 bg-white rounded-lg shadow-sm border">
                <!-- Custom price range -->
                <div class="space-y-4">
                    <!--<h3 class="font-semibold text-lg">Filters</h3>-->
                    
                    <div class="space-y-3">
                        <h4 class="font-medium text-sm text-gray-700">Price Range</h4>
                        <div class="flex gap-2 items-center">
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₱</span>
                                <input
                                    type="number"
                                    v-model="filters.minPrice"
                                    placeholder="Min"
                                    class="w-full pl-7 pr-3 py-1.5 border rounded-md text-sm"
                                    @change="applyFilter({ minPrice: $event.target.value })"
                                />
                            </div>
                            <span class="text-gray-400">-</span>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₱</span>
                                <input
                                    type="number"
                                    v-model="filters.maxPrice"
                                    placeholder="Max"
                                    class="w-full pl-7 pr-3 py-1.5 border rounded-md text-sm"
                                    @change="applyFilter({ maxPrice: $event.target.value })"
                                />
                            </div>
                        </div>

                        <!-- Predefined price ranges -->
                        <div class="space-y-1.5">
                            <button
                                v-for="range in priceRanges"
                                :key="range.id"
                                @click="applyFilter({ priceRange: filters.priceRange === range.id ? null : range.id })"
                                :class="[
                                    'w-full text-left px-3 py-2 rounded-md text-sm transition-colors',
                                    filters.priceRange === range.id
                                        ? 'bg-primary text-white font-medium'
                                        : 'text-gray-700 hover:bg-gray-100'
                                ]"
                            >
                                {{ range.label }}
                            </button>
                        </div>
                    </div>

                    <div class="border-t my-4"></div>

                    <!-- Time frame filter -->
                    <div class="space-y-3">
                        <h4 class="font-medium text-sm text-gray-700">Time Frame</h4>
                        <div class="space-y-1.5">
                            <button
                                v-for="time in timeFrames"
                                :key="time.id"
                                @click="applyFilter({ timeFrame: filters.timeFrame === time.id ? null : time.id })"
                                :class="[
                                    'w-full text-left px-3 py-2 rounded-md text-sm transition-colors',
                                    filters.timeFrame === time.id
                                        ? 'bg-primary text-white font-medium'
                                        : 'text-gray-700 hover:bg-gray-100'
                                ]"
                            >
                                {{ time.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Clear all filters -->
                    <button
                        v-if="filters.minPrice || filters.maxPrice || filters.priceRange || filters.timeFrame"
                        @click="clearAllFilters"
                        class="w-full mt-4 px-3 py-2 text-sm text-primary hover:bg-gray-50 rounded-md border border-primary/20 transition-colors"
                    >
                        Clear all filters
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>