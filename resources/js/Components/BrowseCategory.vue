<template>
	<div>
		<h2 class="text-2xl font-semibold tracking-tight">Browse Categories</h2>
		<Separator class="my-4" />
		<div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-4">
			<!-- Loop through categories -->
			<div
				class="cursor-pointer rounded-lg border p-3 h-20 shadow-md hover:shadow-lg transition-all flex items-center space-x-3"
				v-for="category in categories"
				:key="category.id"
			>
				<!-- img -->
				<img
					:src="category.image"
					:alt="category.name"
					class="w-8 h-8 object-cover rounded-full"
				/>
				<!-- content -->
				<div class="flex flex-col">
					<p class="text-xs">{{ category.name }}</p>
					<p class="text-xs text-gray-500">
						{{ category.listings_count }} items available
					</p>
				</div>
			</div>
		</div>
	</div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { Separator } from "@/components/ui/separator";

// Holds the fetched categories
const categories = ref([]);

// Fetch categories from the API endpoint
const fetchCategories = async () => {
	try {
		const response = await fetch("/api/categories");
		const data = await response.json();
		categories.value = data;
	} catch (error) {
		console.error("Error fetching categories:", error);
	}
};

// Fetch categories when the component is mounted
onMounted(() => {
	fetchCategories();
});
</script>
