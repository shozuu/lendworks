<template>
	<div>
		<h2 class="text-2xl font-semibold tracking-tight">Browse Categories</h2>
		<Separator class="my-4" />
		<div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-5">
			<!-- Loop through categories -->
			<div
				class="cursor-pointer rounded-lg border p-2 w-54 shadow-md hover:shadow-lg transition-all flex items-center space-x-3"
				v-for="category in categories"
				:key="category.id"
			>
				<!-- img -->
				<img
					:src="category.image"
					:alt="category.name"
					class="w-10 h-10 object-cover rounded-full"
				/>
				<!-- content -->
				<div class="flex flex-col">
					<p class="text-xs">{{ category.name }}</p>
					<p class="text-xs text-gray-500">{{ category.items }} items available</p>
				</div>
			</div>
		</div>
	</div>
</template>

<script setup>
import { Separator } from "@/components/ui/separator";

defineProps({
	categories: {
		type: Array,
		required: true,
	},
});
</script>
