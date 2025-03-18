<script setup>
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import PickupScheduleManager from "@/Components/PickupScheduleManager.vue";
import { Calendar, X } from "lucide-vue-next";

defineProps({
	open: Boolean,
	schedules: Array,
});

const emit = defineEmits(["update:open"]);
</script>

<template>
	<Dialog
		:open="open"
		@update:open="emit('update:open', $event)"
		:modal="true"
		@close="emit('update:open', false)"
		@keydown="handleKeyDown"
	>
		<DialogTrigger asChild>
			<Button class="gap-2">
				<Calendar class="w-4 h-4" />
				Manage Availability
			</Button>
		</DialogTrigger>
		<DialogContent
			class="flex flex-col w-[90vw] h-[90vh] md:w-[800px] md:h-[85vh] lg:w-[900px] xl:w-[1000px] p-0 rounded-lg"
		>
			<!-- Close button -->
			<button
				class="absolute right-4 top-4 z-50 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:pointer-events-none data-[state=open]:bg-accent data-[state=open]:text-muted-foreground"
				@click="emit('update:open', false)"
			>
				<X class="h-4 w-4" />
				<span class="sr-only">Close</span>
			</button>

			<div class="flex-1 h-full overflow-hidden">
				<PickupScheduleManager :schedules="schedules" />
			</div>
		</DialogContent>
	</Dialog>
</template>
