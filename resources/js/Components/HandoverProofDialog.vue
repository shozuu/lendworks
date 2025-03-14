<script setup>
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { formatDateTime } from "@/lib/formatters";
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

const title = computed(() => {
	return props.type === "receive" ? "Item Receipt Proof" : "Item Handover Proof";
});

const actionText = computed(() => {
	return props.type === "receive" ? "received" : "handed over";
});
</script>

<template>
	<Dialog :open="show" @update:open="onClose">
		<DialogContent class="sm:max-w-xl">
			<DialogHeader>
				<DialogTitle>{{ title }}</DialogTitle>
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

				<!-- Image -->
				<div class="relative w-full">
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
