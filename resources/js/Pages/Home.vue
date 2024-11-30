<script setup>
import ItemCard from "@/Components/ItemCard.vue";
import { Separator } from "@/components/ui/separator";
import CTAHome from "@/Components/CTAHome.vue";
import { ScrollArea, ScrollBar } from "@/components/ui/scroll-area";
import categoryCard from "@/Components/categoryCard.vue";
import HIWHome from "@/Components/HIWHome.vue";

defineProps({
	CTAImage: String,
	listings: Array,
	newlyListed: Array,
	categories: Array,
});
</script>

<template>
	<Head title="| Home" />

	<div class="space-y-12">
		<!-- CTA image -->
		<CTAHome :CTAImage="CTAImage" />

		<!-- popular tools -->
		<div>
			<div class="flex items-center justify-between">
				<div class="space-y-1">
					<h2 class="text-2xl font-semibold tracking-tight">Top Rented Tools</h2>
					<p class="text-muted-foreground text-sm">
						Discover the most popular tools rented by our users.
					</p>
				</div>
			</div>

			<Separator class="my-4" />

			<div v-if="listings.length">
				<div
					class="sm:grid-cols-2 lg:grid-cols-4 lg:gap-y-6 xl:gap-6 xl:gap-y-8 auto-rows-fr grid grid-cols-1 gap-4"
				>
					<div v-for="listing in listings" :key="listing.id">
						<ItemCard :listing="listing" />
					</div>
				</div>
			</div>
		</div>

		<!-- browse categories -->
		<div>
			<div class="flex items-center justify-between">
				<div class="space-y-1">
					<h2 class="text-2xl font-semibold tracking-tight">Browse Categories</h2>
					<p class="text-muted-foreground text-sm">
						Discover the most popular tools rented by our users.
					</p>
				</div>
			</div>

			<Separator class="my-4" />

			<div v-if="categories">
				<div class="md:grid-cols-3 lg:grid-cols-4 grid grid-cols-2 gap-2">
					<div v-for="category in categories" :key="category.id">
						<categoryCard :category="category" />
					</div>
				</div>
			</div>
		</div>

		<!-- newly listed -->
		<div>
			<div class="flex items-center justify-between">
				<div class="space-y-1">
					<h2 class="text-2xl font-semibold tracking-tight">Newly Listed for Rent</h2>
					<p class="text-muted-foreground text-sm">
						Check the newly listed items available for rent.
					</p>
				</div>
			</div>

			<Separator class="my-4" />

			<ScrollArea>
				<div class="max-w-28 flex space-x-4">
					<div v-for="listing in newlyListed" class="min-w-60 pb-6">
						<ItemCard :listing="listing" />
					</div>
				</div>
				<ScrollBar orientation="horizontal" />
			</ScrollArea>
		</div>

		<!-- how it works -->
		<HIWHome />
	</div>
</template>
