<script setup>
import ItemCard from "@/Components/ItemCard.vue";
import { Separator } from "@/components/ui/separator";
import CTAHome from "@/Components/CTAHome.vue";
import { ScrollArea, ScrollBar } from "@/Components/ui/scroll-area/index";
import BrowseCategories from "@/Components/BrowseCategory.vue";
import HowitWorks from "@/Components/HowitWorks.vue";
import Footer from "@/Components/footer.vue";

defineProps({
	listings: Array,
	NewlyListed: Array,
	CTAImage: String,
});
</script>

<template>
	<Head title="| Home" />

	<div class="space-y-12">
		<!-- CTA Section -->
		<CTAHome :CTAImage="CTAImage" />

		<!-- Popular Tools Section -->
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

		<br />
		<br />

		<!-- Browse Categories Section -->
		<div>
			<BrowseCategories />
		</div>

		<br />
		<br />

		<!-- Newly Listed for Rent Section -->
		<div>
			<div class="flex items-center justify-between">
				<div class="space-y-1">
					<h2 class="text-2xl font-semibold tracking-tight">Newly Listed for Rent</h2>
					<p class="text-sm text-muted-foreground">
						Explore the latest items available for rent.
					</p>
				</div>
			</div>

			<Separator class="my-4" />

			<ScrollArea class="h-[360px] overflow-x-auto border rounded-md">
				<div class="flex p-4 space-x-4 max-w-screen-lg">
					<div v-for="item in NewlyListed" :key="item.id" class="shrink-0">
						<!-- Horizontal format -->
						<div class="overflow-hidden rounded-md max-w-xs h-auto">
							<ItemCard :listing="item" />
						</div>
					</div>
				</div>

				<ScrollBar orientation="horizontal" />
			</ScrollArea>
		</div>

		<div>
			<HowitWorks />
		</div>
	</div>

	<br />
	<br />
	<br />

	<div>
		<Footer />
	</div>
</template>
