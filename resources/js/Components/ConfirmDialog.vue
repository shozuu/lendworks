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
	"keydown",
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

const isSelectReasonOther = computed(() => {
	if (!props.selectOptions || !props.selectValue) return false;
	const selectedOption = props.selectOptions.find(
		(opt) => opt.value === props.selectValue
	);
	return selectedOption?.code === "other";
});

const sanitizeInput = (input) => {
	if (!input) return "";
	return input
		.replace(/[<>]/g, "") // Remove < and > characters
		.trim();
};

const handleTextareaInput = (event) => {
	const sanitizedValue = sanitizeInput(event.target.value);
	emits("update:textareaValue", sanitizedValue);
};

const isDisabled = computed(() => {
	if (props.processing) return true;
	if (props.disabled) return true;

	const textareaContent = props.textareaValue?.trim() || "";

	if (isSelectReasonOther.value) {
		// Enforce minimum and maximum length for custom feedback
		return textareaContent.length < 10 || textareaContent.length > 1000;
	}

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
		<DialogContent
			class="w-[calc(100vw-2rem)] sm:max-w-[425px] p-0 flex flex-col max-h-[calc(100vh-2rem)] overflow-hidden rounded-lg"
		>
			<!-- Fixed Header -->
			<DialogHeader class="p-4 sm:p-6">
				<DialogTitle class="text-lg sm:text-xl">{{ title }}</DialogTitle>
				<DialogDescription class="mt-2 sm:mt-3">
					{{ description }}
				</DialogDescription>
			</DialogHeader>

			<!-- Scrollable Content Area -->
			<div class="flex-1 px-4 sm:px-6" v-if="showSelect">
				<!-- Select input -->
				<div class="space-y-2 mb-4">
					<Select
						:model-value="selectValue"
						@update:model-value="(value) => $emit('update:selectValue', value)"
					>
						<SelectTrigger class="w-full">
							<SelectValue :placeholder="'Select a reason...'" />
						</SelectTrigger>
						<SelectContent class="max-h-[200px]">
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

				<!-- Textarea sections -->
				<div v-if="showTextarea || isSelectReasonOther" class="space-y-3">
					<textarea
						:value="textareaValue"
						@input="handleTextareaInput"
						:placeholder="textareaPlaceholder"
						:minLength="textareaMinLength"
						class="min-h-[120px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm resize-y"
						:class="{
							'border-destructive': textAreaError,
							'focus:border-primary': !textAreaError,
						}"
					></textarea>

					<div class="space-y-2 text-sm">
						<p v-if="textAreaError" class="text-destructive">
							{{ textAreaError }}
						</p>
						<p class="text-muted-foreground text-xs">
							{{
								isSelectReasonOther
									? "Please provide a clear and specific reason that will help the user understand why their listing was rejected."
									: "Please provide a clear and specific reason that will help the owner understand why their listing was taken down."
							}}
						</p>
						<div v-if="textareaValue" class="text-muted-foreground text-xs">
							{{ textareaValue.length }}/1000 characters
						</div>
					</div>
				</div>
			</div>

			<!-- Fixed Footer -->
			<DialogFooter class="p-4 sm:p-6">
				<div
					class="sm:flex-row sm:gap-3 sm:justify-end flex flex-col-reverse w-full gap-2"
				>
					<Button
						variant="outline"
						@click="$emit('cancel')"
						:disabled="processing"
						ref="cancelButton"
						class="sm:w-auto w-full"
					>
						Cancel
					</Button>
					<Button
						:variant="confirmVariant"
						:disabled="isDisabled"
						@click="handleConfirm"
						class="sm:w-auto w-full"
					>
						<template v-if="processing">
							<span class="animate-spin inline-block mr-2">⏳</span>
							{{ confirmLabel.replace(/e?$/, "ing...") }}
						</template>
						<template v-else>
							{{ confirmLabel }}
						</template>
					</Button>
				</div>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>

<style scoped>
.dialog-content {
	@apply fixed left-[50%] top-[50%] translate-x-[-50%] translate-y-[-50%] rounded-lg bg-background shadow-lg;
}

/* Improved scrollbar styling */
textarea {
	scrollbar-width: thin;
	scrollbar-color: hsl(var(--muted)) transparent;
}

textarea::-webkit-scrollbar {
	width: 4px;
}

textarea::-webkit-scrollbar-track {
	background: transparent;
}

textarea::-webkit-scrollbar-thumb {
	background-color: hsl(var(--muted));
	border-radius: 4px;
}

@media (min-width: 640px) {
	textarea::-webkit-scrollbar {
		width: 6px;
	}
}

/* Ensure dialog content doesn't overflow on mobile */
:deep(.dialog-content) {
	position: fixed;
	left: 50%;
	top: 50%;
	transform: translate(-50%, -50%);
	margin: 1rem;
	width: calc(100vw - 2rem);
	max-height: calc(100vh - 2rem);
	background: hsl(var(--background));
	box-shadow: var(--shadow-lg);
}

@media (min-width: 640px) {
	:deep(.dialog-content) {
		margin: 0;
		width: 100%;
	}
}

/* Ensure textarea is properly styled */
textarea {
	min-height: 120px;
	max-height: 300px;
}
</style>
