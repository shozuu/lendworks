<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
	variant: {
		type: String,
		default: "default",
	},
});

const variants = {
	default: "bg-muted hover:bg-muted/80 border-transparent text-muted-foreground",
	red: "bg-red-100 hover:bg-red-100/80 border-transparent text-red-600",
	green: "bg-green-100 hover:bg-green-100/80 border-transparent text-green-600",
	yellow: "bg-yellow-100 hover:bg-yellow-100/80 border-transparent text-yellow-600",
	blue: "bg-blue-100 hover:bg-blue-100/80 border-transparent text-blue-600",
	purple: "bg-purple-100 hover:bg-purple-100/80 border-transparent text-purple-600",
	orange: "bg-orange-100 hover:bg-orange-100/80 border-transparent text-orange-600",
};
</script>

<template>
	<div
		:class="
			cn(
				'inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-full select-none',
				variants[variant]
			)
		"
	>
		<slot />
	</div>
</template>
