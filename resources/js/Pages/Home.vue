<script setup>
import ItemCard from "@/Components/ItemCard.vue";
import { Separator } from "@/components/ui/separator";
import CTAHome from "@/Components/CTAHome.vue";

defineProps({
	listings: Object,
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
					<h2 class="text-2xl font-semibold tracking-tight">Rent Now</h2>
					<p class="text-sm text-muted-foreground">
						Top-rated tools, handpicked for your projects. Available today!
					</p>
				</div>
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
			</div>
		</div>
	</div>
</template>
