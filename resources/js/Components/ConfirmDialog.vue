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
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import { ref, onMounted, computed } from "vue";

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
	disabled: {
		type: Boolean,
		default: false,
	},
	processing: {
		type: Boolean,
		default: false,
	},
	closeOnSuccess: {
		type: Boolean,
		default: true,
	},
	textareaMinLength: {
		type: String,
		default: "0",
	},
	textAreaError: {
		type: String,
		default: "",
	},
	showSelect: {
		type: Boolean,
		default: false,
	},
	selectOptions: {
		type: Array,
		default: () => [],
	},
	selectValue: String,
});

const emits = defineEmits([
	"update:show",
	"update:textareaValue",
	"confirm",
	"cancel",
	"update:selectValue",
	"keydown", // Add keydown to emits
]);

const handleConfirm = async () => {
	try {
		await emits("confirm");
	} catch (error) {}
};

// Add focus management
const cancelButton = ref(null);

onMounted(() => {
	if (cancelButton.value) {
		cancelButton.value.focus();
	}
});

const handleKeyDown = (event) => {
	if (event.key === "Escape") {
		emits("cancel");
	}
};

const isDisabled = computed(() => {
	if (props.processing) return true;
	if (props.showTextarea && !props.textareaValue?.trim()) return true;
	if (props.showSelect && !props.selectValue) return true;
	if (props.disabled) return true;
	return false;
});
</script>

<template>
	<Dialog
		:open="show"
		@update:open="$emit('update:show', $event)"
		:modal="true"
		@close="$emit('cancel')"
		@keydown="handleKeyDown"
	>
		<DialogContent>
			<DialogHeader>
				<DialogTitle>{{ title }}</DialogTitle>
				<DialogDescription>{{ description }}</DialogDescription>
			</DialogHeader>

			<div v-if="showTextarea" class="space-y-2 py-4">
				<textarea
					:value="textareaValue"
					@input="$emit('update:textareaValue', $event.target.value)"
					:placeholder="textareaPlaceholder"
					:minLength="textareaMinLength"
					class="flex min-h-[120px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus:border-primary disabled:cursor-not-allowed disabled:opacity-50"
				></textarea>
				<p v-if="textAreaError" class="text-sm text-destructive">
					{{ textAreaError }}
				</p>
				<p class="text-xs text-muted-foreground">
					Please provide a clear and specific reason that will help the owner understand
					why their listing was taken down.
				</p>
			</div>

			<div v-if="showSelect" class="py-4">
				<Select
					:model-value="selectValue"
					@update:model-value="(value) => $emit('update:selectValue', value)"
				>
					<SelectTrigger class="w-full">
						<SelectValue :placeholder="'Select a reason...'" />
					</SelectTrigger>
					<SelectContent>
						<SelectItem
							v-for="option in selectOptions"
							:key="option.value"
							:value="option.value"
							class="cursor-pointer"
						>
							{{ option.label }}
						</SelectItem>
					</SelectContent>
				</Select>
			</div>

			<DialogFooter>
				<Button
					variant="outline"
					@click="$emit('cancel')"
					:disabled="processing"
					ref="cancelButton"
					tabindex="0"
				>
					Cancel
				</Button>
				<Button
					:variant="confirmVariant"
					:disabled="isDisabled"
					@click="handleConfirm"
					tabindex="0"
				>
					<template v-if="processing">
						<span class="inline-block animate-spin mr-2">⏳</span>
						{{
							confirmLabel === "Approve"
								? "Approving..."
								: confirmLabel === "Reject"
								? "Rejecting..."
								: confirmLabel === "Delete"
								? "Deleting..."
								: `${confirmLabel}...`
						}}
					</template>
					<template v-else>
						{{ confirmLabel }}
					</template>
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>

<style>
.v-select-item {
	@apply relative flex cursor-pointer select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50;
}

.v-select-item[data-highlighted] {
	@apply bg-accent text-accent-foreground;
}

.v-select-item[data-state="checked"] {
	@apply bg-accent text-accent-foreground;
}
</style>
