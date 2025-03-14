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
		<DialogContent class="sm:max-w-[800px] h-[calc(100vh-4rem)]">
			<div class="flex-1 overflow-hidden">
				<PickupScheduleManager :schedules="schedules" />
			</div>
		</DialogContent>
	</Dialog>
</template>
