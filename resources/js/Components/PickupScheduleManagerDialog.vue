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
import { Calendar } from "lucide-vue-next";

defineProps({
	open: Boolean,
	schedules: Array,
});

const emit = defineEmits(["update:open"]);
</script>

<template>
	<Dialog :open="open" @update:open="emit('update:open', $event)">
		<DialogTrigger asChild>
			<Button class="gap-2">
				<Calendar class="w-4 h-4" />
				Manage Availability
			</Button>
		</DialogTrigger>
		<DialogContent
			class="w-[calc(100vw-2rem)] h-[calc(100vh-2rem)] sm:max-w-[640px] md:max-w-[800px] p-0 gap-0"
		>
			<div class="flex-1 overflow-hidden">
				<PickupScheduleManager :schedules="schedules" />
			</div>
		</DialogContent>
	</Dialog>
</template>
