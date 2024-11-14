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
            class="block mb-2 text-sm font-medium text-foreground" 
        >
            {{ label }}
        </label>

        <div class="relative mt-1 rounded-md">
            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
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
                    'block w-full rounded-lg bg-transparent text-foreground border-input py-4 pr-4 pl-9 text-sm focus:ring-transparent focus:border-primary placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50', 
                    error ? 'border-destructive focus:ring-destructive' : '' 
                ]" 
            />
        </div>
        <p v-if="error" class="mt-1 text-sm text-destructive">{{ error }}</p>
    </div>
</template>
