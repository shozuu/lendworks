<script setup>
    const model = defineModel();
    defineProps({
        label: String,
        icon: String,
        placeholder: {
            type: String,
            default: "",
        },
        type: {
            type: String,
            default: "text",
        },
        error: {
            type: String,
            default: null,
        },
        readonly: {
            type: Boolean,
            required: false,
        },
    });
</script>

<template>
    <div>
        <label
            :for="label"
            class="block text-sm font-semibold mb-2 text-foreground" 
        >
            {{ label }}
        </label>

        <div class="relative mt-1 rounded-md">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <span class="grid place-content-center text-sm text-muted-foreground">
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
                    'block w-full rounded-lg bg-transparent text-foreground border-input py-4 pr-4 pl-9 text-sm focus:ring-transparent focus:border-primary placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50', 
                    error ? 'border-destructive focus:ring-destructive' : '' 
                ]" 
            />
        </div>
        <p v-if="error" class="text-destructive text-sm mt-1">{{ error }}</p>
    </div>
</template>
