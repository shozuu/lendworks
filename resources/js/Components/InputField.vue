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
    });
</script>

<template>
    <div>
        <label
        :for="label"
        class="block text-sm font-semibold mb-2 text-text"
        >
        {{ label }}
        </label>

        <div class="relative mt-1 rounded-md">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <span class="grid place-content-center text-sm text-text-muted">
                <i :class="`fa-solid fa-${icon}`"></i>
                </span>
            </div>
            <input
                :type="type"
                :id="label"
                :name="label"
                :placeholder="placeholder"
                v-model="model"
                :class="[
                    'block w-full rounded-md bg-secondary text-text border-border outline-0 py-2 pr-3 pl-9 text-sm shadow-sm focus:ring-2 focus:ring-inset focus:ring-primary focus:border-primary placeholder:text-text-muted transition-all',
                    error ? 'border-warning focus:ring-warning' : ''
                ]"
            />
        </div>
        
        <p v-if="error" class="text-warning text-sm mt-1">{{ error }}</p>
    </div>
</template>
