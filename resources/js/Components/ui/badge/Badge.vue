<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
	variant: {
		type: String,
		default: "default",
	},
});

const variants = {
	default: "bg-secondary hover:bg-secondary/80 text-secondary-foreground",
	primary: "bg-primary text-primary-foreground hover:bg-primary/80",
	success: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100",
	destructive: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100",
	warning: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100",
	info: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100",
	outline: "text-foreground border border-input",
	muted: "bg-muted text-muted-foreground",
};
</script>

<template>
	<div
		:class="
			cn(
				'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
				variants[variant]
			)
		"
	>
		<slot />
	</div>
</template>
