<script setup>
const model = defineModel();
defineProps({
	label: { type: String, default: "" },
	icon: { type: String, default: "" },
	placeholder: { type: String, default: "" },
	type: { type: String, default: "text" },
	error: { type: String, default: null },
	readonly: { type: Boolean, required: false },
	addedClass: { type: [String, Array, Object], default: "" },
	bg: { type: String, default: null },
});
</script>

<template>
	<label v-if="label" :for="label" class="block mb-2 text-sm font-medium text-foreground">
		{{ label }}
	</label>

	<div class="relative">
		<div
			v-if="icon"
			class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none"
		>
			<span class="grid text-sm place-content-center text-muted-foreground">
				<i :class="`fa-solid fa-${icon}`"></i>
			</span>
		</div>
		<input
			:readonly="readonly"
			:type="type"
			:id="label"
			:name="label"
			:placeholder="placeholder"
			v-model="model"
			:class="[
				'block w-full rounded-lg text-foreground border-input py-4 px-3 pl-9 text-sm focus:ring-transparent focus:border-primary placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50',
				error ? 'border-destructive focus:ring-destructive' : '',
				addedClass,
				bg ? bg : 'bg-transparent',
			]"
		/>
	</div>
	<p v-if="error" class="mt-1 text-sm text-destructive">{{ error }}</p>
</template>
