<script setup>
defineProps({
	category: Object,
});
</script>

<template>
	<Link
		href=""
		class="hover:border-primary flex items-center p-3 py-4 lg:space-x-3 transition-all border rounded-lg"
	>
		<img
			src="https://picsum.photos/200/300"
			:alt="category.name"
			class="shrink-0 lg:flex hidden object-cover w-8 h-8 rounded-full"
		/>
		<!-- content -->
		<div class="flex flex-col">
			<p class="line-clamp-1 text-sm font-semibold">{{ category.name }}</p>
			<p class="text-foreground line-clamp-1 text-xs">{{ category.description }}</p>
		</div>
	</Link>
</template>
