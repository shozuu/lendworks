<script setup>
import ItemCard from "@/Components/ItemCard.vue";
import { Separator } from "@/components/ui/separator";
import CTAHome from "@/Components/CTAHome.vue";

defineProps({
	listings: Array,
	CTAImage: String,
});
</script>

<template>
	<Head title="| Home" />

	<div class="space-y-12">
		<!-- CTA -->
		<CTAHome :CTAImage="CTAImage" />

		<!-- popular tools -->
		<div>
			<div class="flex items-center justify-between">
				<div class="space-y-1">
					<h2 class="text-2xl font-semibold tracking-tight">Top Rented Tools</h2>
					<p class="text-sm text-muted-foreground">
						Discover the most popular tools rented by our users.
					</p>
				</div>
			</div>

			<Separator class="my-4" />

			<div v-if="listings.length">
				<div
					class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-y-6 xl:gap-6 xl:gap-y-8 auto-rows-fr"
				>
					<div v-for="listing in listings" :key="listing.id">
						<ItemCard :listing="listing" />
					</div>
				</div>
			</div>
		</div>
	</div>
</template>