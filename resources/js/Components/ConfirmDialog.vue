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
	forceShowTextarea: {
		type: Boolean,
		default: false,
	},
	showQuantity: {
		type: Boolean,
		default: false,
	},
	quantityValue: {
		type: Number,
		default: 1,
	},
	maxQuantity: {
		type: Number,
		default: 1,
	},
});

const emits = defineEmits([
	"update:show",
	"update:textareaValue",
	"confirm",
	"cancel",
	"update:selectValue",
	"keydown",
	"update:quantityValue",
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

	if (props.showTextarea || isSelectReasonOther.value) {
		// Enforce minimum and maximum length for custom feedback
		return textareaContent.length < 10 || textareaContent.length > 1000;
	}

	// If select is shown but no value is selected
	if (props.showSelect && !props.selectValue) {
		return true;
	}

	return false;
});

const shouldShowTextarea = computed(() => {
	if (props.forceShowTextarea) return true;
	if (!props.showSelect) return props.showTextarea;
	return isSelectReasonOther.value;
});

const incrementQuantity = () => {
	if (props.quantityValue < props.maxQuantity) {
		emits("update:quantityValue", props.quantityValue + 1);
	}
};

const decrementQuantity = () => {
	if (props.quantityValue > 1) {
		emits("update:quantityValue", props.quantityValue - 1);
	}
};

const handleQuantityInput = (event) => {
	const value = parseInt(event.target.value);
	if (isNaN(value)) return;

	// Clamp value between 1 and maxQuantity
	const clampedValue = Math.min(Math.max(1, value), props.maxQuantity);
	emits("update:quantityValue", clampedValue);
};
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
			<DialogHeader class="sm:p-6 p-4">
				<DialogTitle class="sm:text-xl text-lg">{{ title }}</DialogTitle>
				<DialogDescription class="sm:mt-3 mt-2">
					{{ description }}
				</DialogDescription>
			</DialogHeader>

			<!-- Scrollable Content Area -->
			<div class="sm:px-6 flex-1 px-4">
				<!-- Enhanced quantity input -->
				<div v-if="showQuantity" class="space-y-3 mb-4">
					<div class="flex items-center justify-between">
						<label class="text-foreground text-sm font-medium">Quantity to Approve</label>
						<span class="text-muted-foreground text-xs">
							Maximum: {{ maxQuantity }} unit(s)
						</span>
					</div>

					<div class="flex items-center gap-3">
						<Button
							type="button"
							variant="outline"
							size="icon"
							class="h-9 w-9 shrink-0"
							:disabled="quantityValue <= 1"
							@click="decrementQuantity"
						>
							-
						</Button>

						<div class="relative flex-1">
							<input
								type="number"
								:value="quantityValue"
								@input="handleQuantityInput"
								min="1"
								:max="maxQuantity"
								class="w-full h-9 px-3 text-center rounded-md border border-input bg-background text-foreground ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
							/>
						</div>

						<Button
							type="button"
							variant="outline"
							size="icon"
							class="h-9 w-9 shrink-0"
							:disabled="quantityValue >= maxQuantity"
							@click="incrementQuantity"
						>
							+
						</Button>
					</div>
				</div>
				<!-- Select input -->
				<div v-if="showSelect" class="mb-4 space-y-2">
					<!-- Select input -->
					<div class="mb-4 space-y-2">
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
				</div>

				<!-- Textarea sections -->
				<div v-if="shouldShowTextarea" class="space-y-3">
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
			<DialogFooter class="sm:p-6 p-4">
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
							{{
								confirmLabel === "Take Down"
									? "Taking Down..."
									: confirmLabel.replace(/e$/, "ing")
							}}
							<span class="animate-spin inline-block mr-2">⏳</span>
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

/* Update input number styles */
input[type="number"] {
    @apply text-base font-medium;
    -moz-appearance: textfield;
}

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Ensure proper input color contrast */
input[type="number"]::placeholder {
    @apply text-muted-foreground/60;
}

input[type="number"]:focus {
    @apply border-ring;
}

input[type="number"] {
	-moz-appearance: textfield;
}

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
	-webkit-appearance: none;
	margin: 0;
}
</style>
