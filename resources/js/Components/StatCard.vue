<script setup>
defineProps({
	label: {
		type: String,
		required: true,
	},
	value: {
		type: Number,
		required: true,
	},
});
</script>

<template>
	<div class="p-3 rounded-lg border bg-card flex flex-col">
		<p class="text-sm text-muted-foreground truncate">{{ label }}</p>
		<p class="text-lg sm:text-xl lg:text-2xl font-semibold mt-1">
			{{ value }}
		</p>
	</div>
</template>
