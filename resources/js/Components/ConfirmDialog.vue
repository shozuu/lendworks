<script setup>
import {
	Dialog,
	DialogContent,
	DialogFooter,
	DialogHeader,
	DialogTitle,
	DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

const props = defineProps({
	show: Boolean,
	title: String,
	description: String,
	confirmLabel: {
		type: String,
		default: "Confirm",
	},
	confirmVariant: {
		type: String,
		default: "default",
	},
	showTextarea: {
		type: Boolean,
		default: false,
	},
	textareaValue: String,
	textareaPlaceholder: String,
});

const emits = defineEmits(["update:show", "update:textareaValue", "confirm", "cancel"]);
</script>

<template>
	<Dialog :open="show" @update:open="$emit('update:show', $event)">
		<DialogContent>
			<DialogHeader>
				<DialogTitle>{{ title }}</DialogTitle>
				<DialogDescription>{{ description }}</DialogDescription>
			</DialogHeader>

			<div v-if="showTextarea" class="py-4">
				<textarea
					:value="textareaValue"
					@input="$emit('update:textareaValue', $event.target.value)"
					:placeholder="textareaPlaceholder"
					class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
				></textarea>
			</div>

			<DialogFooter>
				<Button variant="outline" @click="$emit('cancel')">Cancel</Button>
				<Button
					:variant="confirmVariant"
					:disabled="showTextarea && !textareaValue?.trim()"
					@click="$emit('confirm')"
				>
					{{ confirmLabel }}
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
