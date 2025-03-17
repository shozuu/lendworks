<script setup>
import { Separator } from "@/components/ui/separator";
import { computed } from "vue";

const props = defineProps({
	filters: {
		type: Object,
		required: true,
	},
	priceRanges: Array,
	timeFrames: Array,
	isMobile: Boolean,
});

const emit = defineEmits(["update"]);

// Computed property for managing filters state
const filterState = computed({
	get: () => props.filters,
	set: (newValue) => emit("update", newValue),
});

const updateFilter = (params) => {
	emit("update", params);
};
</script>

<template>
	<div class="space-y-6">
		<!-- Price Range Section -->
		<div class="space-y-4">
			<div class="space-y-1.5">
				<h3 class="text-sm font-medium tracking-tight">Price Range</h3>
				<p class="text-xs text-muted-foreground">
					Set a custom range or choose from presets
				</p>
			</div>

			<div class="space-y-3">
				<!-- Custom Range Inputs -->
				<div class="grid grid-cols-2 gap-2">
					<div class="relative">
						<span
							class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"
							>₱</span
						>
						<input
							type="number"
							:value="filterState.minPrice"
							placeholder="Min"
							class="w-full h-9 px-7 rounded-lg border bg-accent/50 text-sm focus:bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring hover:bg-accent/70 transition-colors"
							@input="updateFilter({ minPrice: $event.target.value })"
						/>
					</div>
					<div class="relative">
						<span
							class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"
							>₱</span
						>
						<input
							type="number"
							:value="filterState.maxPrice"
							placeholder="Max"
							class="w-full h-9 px-7 rounded-lg border bg-accent/50 text-sm focus:bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring hover:bg-accent/70 transition-colors"
							@input="updateFilter({ maxPrice: $event.target.value })"
						/>
					</div>
				</div>

				<!-- Predefined Ranges -->
				<div class="space-y-1">
					<button
						v-for="range in priceRanges"
						:key="range.id"
						@click="
							updateFilter({
								priceRange: filterState.priceRange === range.id ? null : range.id,
							})
						"
						:class="[
							'w-full text-left px-3 py-2 rounded-lg text-sm transition-all',
							filterState.priceRange === range.id
								? 'bg-primary/10 text-primary font-medium shadow-sm'
								: 'text-foreground hover:bg-accent/50',
						]"
					>
						{{ range.label }}
					</button>
				</div>
			</div>
		</div>

		<Separator class="bg-border/40" />

		<!-- Time Frame Section -->
		<div class="space-y-4">
			<div class="space-y-1.5">
				<h3 class="text-sm font-medium tracking-tight">Time Frame</h3>
				<p class="text-xs text-muted-foreground">Filter listings by date posted</p>
			</div>

			<div class="space-y-1">
				<button
					v-for="time in timeFrames"
					:key="time.id"
					@click="
						updateFilter({
							timeFrame: filterState.timeFrame === time.id ? null : time.id,
						})
					"
					:class="[
						'w-full text-left px-3 py-2 rounded-lg text-sm transition-all',
						filterState.timeFrame === time.id
							? 'bg-primary/10 text-primary font-medium shadow-sm'
							: 'text-foreground hover:bg-accent/50',
					]"
				>
					{{ time.label }}
				</button>
			</div>
		</div>
	</div>
</template>
