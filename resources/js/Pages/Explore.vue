<script setup>
import ItemCard from "@/Components/ItemCard.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import Separator from "@/Components/ui/separator/Separator.vue";

const props = defineProps({
	listings: Object,
	searchTerm: String,
});
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
</template>