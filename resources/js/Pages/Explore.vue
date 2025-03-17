<script setup>
import ItemCard from "@/Components/ItemCard.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import Separator from "@/Components/ui/separator/Separator.vue";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from "@/components/ui/sheet";
import { Button } from "@/components/ui/button";
import { ref } from 'vue';
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

const isFilterOpen = ref(false);
const mobileFilters = ref({
    minPrice: props.filters.minPrice,
    maxPrice: props.filters.maxPrice,
    priceRange: props.filters.priceRange,
    timeFrame: props.filters.timeFrame
});

// For desktop filters (immediate application)
const applyFilter = (params) => {
    if (window.innerWidth >= 768) { // md breakpoint
        router.get(route('explore'), {
            ...route().params,
            ...params
        }, {
            preserveState: true,
            preserveScroll: true
        });
    }
};

// For mobile filters (temporary state)
const updateMobileFilter = (params) => {
    mobileFilters.value = {
        ...mobileFilters.value,
        ...params
    };
};

// Apply all mobile filters at once
const applyMobileFilters = () => {
    router.get(route('explore'), {
        ...route().params,
        ...mobileFilters.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
    isFilterOpen.value = false; // Close the sheet after applying
};

// Clear mobile filters
const clearMobileFilters = () => {
    mobileFilters.value = {
        minPrice: null,
        maxPrice: null,
        priceRange: null,
        timeFrame: null
    };
};

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

const clearAllFilters = () => {
    router.get(route('explore'), {
        search: props.searchTerm
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const handleCategoryChange = (categoryId) => {
    router.get(route('explore'), {
        ...route().params, // Keep other existing params
        category: categoryId, // Don't check against selectedCategory
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

    <!-- Mobile Filter Controls -->
    <div class="flex items-center gap-4 mb-6 md:hidden">
        <Select :modelValue="selectedCategory ?? null" @update:modelValue="handleCategoryChange">
            <SelectTrigger class="w-[180px]">
                <SelectValue placeholder="All Categories">
                    {{ selectedCategory ? (categories.find(c => c.id == selectedCategory)?.name) : 'All Categories' }}
                </SelectValue>
            </SelectTrigger>
            <SelectContent>
                <SelectItem :value="null">All Categories</SelectItem>
                <SelectItem v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                </SelectItem>
            </SelectContent>
        </Select>

        <Sheet v-model:open="isFilterOpen">
            <SheetTrigger asChild>
                <Button variant="outline" class="gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z" />
                    </svg>
                    Filters
                </Button>
            </SheetTrigger>
            <SheetContent side="right" class="w-full sm:w-[380px] flex flex-col dark:border-l dark:border-border">
                <SheetHeader>
                    <SheetTitle>Filters</SheetTitle>
                </SheetHeader>
                <!-- Filter Content -->
                <div class="flex-1 mt-6 space-y-6 overflow-y-auto">
                    <!-- Price Range Section -->
                    <div class="space-y-3">
                        <div>
                            <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300">Daily Rate</h4>
                            <p class="text-xs text-muted-foreground mt-1">Enter custom range or select a predefined range below</p>
                        </div>
                        <!-- Add min/max price inputs -->
                        <div class="flex gap-2 items-center mb-3">
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500 dark:text-gray-400">₱</span>
                                <input
                                    type="number"
                                    v-model="mobileFilters.minPrice"
                                    placeholder="Min"
                                    class="w-full pl-7 pr-3 py-1.5 border rounded-md text-sm bg-background dark:border-border"
                                />
                            </div>
                            <span class="text-gray-400 dark:text-gray-500">-</span>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500 dark:text-gray-400">₱</span>
                                <input
                                    type="number"
                                    v-model="mobileFilters.maxPrice"
                                    placeholder="Max"
                                    class="w-full pl-7 pr-3 py-1.5 border rounded-md text-sm bg-background dark:border-border"
                                />
                            </div>
                        </div>
                        
                        <!-- Predefined price ranges -->
                        <div class="space-y-1.5">
                            <button
                                v-for="range in priceRanges"
                                :key="range.id"
                                @click="updateMobileFilter({ priceRange: mobileFilters.priceRange === range.id ? null : range.id })"
                                :class="[
                                    'w-full text-left px-3 py-2 rounded-md text-sm transition-colors',
                                    mobileFilters.priceRange === range.id
                                        ? 'bg-primary text-primary-foreground dark:text-primary-foreground font-medium'
                                        : 'text-foreground hover:bg-muted dark:hover:bg-muted'
                                ]"
                            >
                                {{ range.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Time Frame Section -->
                    <div class="space-y-3">
                        <div>
                            <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300">Time Frame</h4>
                            <p class="text-xs text-muted-foreground mt-1">Filter tools by when they were listed</p>
                        </div>
                        <div class="space-y-1.5">
                            <button
                                v-for="time in timeFrames"
                                :key="time.id"
                                @click="updateMobileFilter({ timeFrame: mobileFilters.timeFrame === time.id ? null : time.id })"
                                :class="[
                                    'w-full text-left px-3 py-2 rounded-md text-sm transition-colors',
                                    mobileFilters.timeFrame === time.id
                                        ? 'bg-primary text-primary-foreground dark:text-primary-foreground font-medium'
                                        : 'text-foreground hover:bg-muted dark:hover:bg-muted'
                                ]"
                            >
                                {{ time.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="sticky bottom-0 border-t dark:border-border bg-background p-4 mt-auto space-y-2">
                    <Button
                        @click="applyMobileFilters"
                        class="w-full"
                    >
                        Apply Filters
                    </Button>
                    <Button
                        v-if="Object.values(mobileFilters).some(v => v !== null)"
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

    <!-- Desktop Category Filters -->
    <div class="mb-6 hidden md:flex flex-wrap gap-2">
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

    <div class="flex flex-col gap-6 md:flex-row">
        <!-- Desktop Sidebar Filters -->
        <div class="hidden md:block w-72 space-y-8">
            <div class="p-6 bg-background rounded-lg shadow-sm border dark:border-border">
                <!-- Custom price range -->
                <div class="space-y-4">
                    <!--<h3 class="font-semibold text-lg">Filters</h3>-->
                    
                    <div class="space-y-3">
                        <div>
                            <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300">Daily Rate</h4>
                            <p class="text-xs text-muted-foreground mt-1">Enter custom range or select a predefined range below</p>
                        </div>
                        <div class="flex gap-2 items-center">
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500 dark:text-gray-400">₱</span>
                                <input
                                    type="number"
                                    v-model="filters.minPrice"
                                    placeholder="Min"
                                    class="w-full pl-7 pr-3 py-1.5 border rounded-md text-sm bg-background dark:border-border"
                                    @change="applyFilter({ minPrice: $event.target.value })"
                                />
                            </div>
                            <span class="text-gray-400 dark:text-gray-500">-</span>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500 dark:text-gray-400">₱</span>
                                <input
                                    type="number"
                                    v-model="filters.maxPrice"
                                    placeholder="Max"
                                    class="w-full pl-7 pr-3 py-1.5 border rounded-md text-sm bg-background dark:border-border"
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
                                        ? 'bg-primary text-primary-foreground dark:text-primary-foreground font-medium'
                                        : 'text-foreground hover:bg-muted dark:hover:bg-muted'
                                ]"
                            >
                                {{ range.label }}
                            </button>
                        </div>
                    </div>

                    <div class="border-t my-4"></div>

                    <!-- Time frame filter -->
                    <div class="space-y-3">
                        <div>
                            <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300">Time Frame</h4>
                            <p class="text-xs text-muted-foreground mt-1">Filter tools by when they were listed</p>
                        </div>
                        <div class="space-y-1.5">
                            <button
                                v-for="time in timeFrames"
                                :key="time.id"
                                @click="applyFilter({ timeFrame: filters.timeFrame === time.id ? null : time.id })"
                                :class="[
                                    'w-full text-left px-3 py-2 rounded-md text-sm transition-colors',
                                    filters.timeFrame === time.id
                                        ? 'bg-primary text-primary-foreground dark:text-primary-foreground font-medium'
                                        : 'text-foreground hover:bg-muted dark:hover:bg-muted'
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

        <!-- Main Content -->
        <div class="flex-1">
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
    </div>
</template>