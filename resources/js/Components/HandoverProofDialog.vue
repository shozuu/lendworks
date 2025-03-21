<script setup>
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { formatDateTime } from "@/lib/formatters";
import { X } from "lucide-vue-next"; // Add X icon import
import { computed } from "vue";

const props = defineProps({
	show: Boolean,
	imagePath: String,
	onClose: Function,
	type: {
		type: String,
		required: true,
	},
	performer: {
		type: Object,
		required: true,
	},
	timestamp: {
		type: String,
		required: true,
	},
});

// Add emit for handling close
const emit = defineEmits(["update:show"]);

const title = computed(() => {
	return props.type === "receive" ? "Item Receipt Proof" : "Item Handover Proof";
});

const actionText = computed(() => {
	return props.type === "receive" ? "received" : "handed over";
});

// Add close handler
const handleClose = () => {
	emit("update:show", false);
};
</script>

<template>
	<Dialog :open="show" @update:open="handleClose">
		<DialogContent class="sm:max-w-xl">
			<DialogHeader>
				<DialogTitle>{{ title }}</DialogTitle>
				<button
					class="absolute right-4 top-4 rounded-sm opacity-70 ring-offset-background transition-opacity hover:opacity-100"
					@click="handleClose"
				>
					<X class="h-4 w-4" />
					<span class="sr-only">Close</span>
				</button>
			</DialogHeader>

			<div class="space-y-4">
				<!-- Action Details -->
				<div class="space-y-2">
					<p class="text-sm text-muted-foreground">
						Item was {{ actionText }} by
						<span class="font-medium">{{ performer.name }}</span>
					</p>
					<p class="text-xs text-muted-foreground">
						{{ formatDateTime(timestamp) }}
					</p>
				</div>

				<!-- Image Container -->
				<div class="relative max-h-[60vh] overflow-auto">
					<img
						:src="`/storage/${imagePath}`"
						:alt="title"
						class="object-contain w-full rounded-lg"
					/>
				</div>
			</div>
		</DialogContent>
	</Dialog>
</template>
