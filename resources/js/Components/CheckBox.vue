<script setup>
    const model = defineModel();

    defineProps({
        name: String,
    });
</script>


<template>        
    <div class="flex items-center gap-2">
        <input 
            type="checkbox"
            v-model="model"
            :id="name"
            class="w-5 h-5 border-2 border-secondary rounded-sm text-primary focus:ring-primary focus:outline-none transition duration-200 cursor-pointer hover:shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
        />
        <label 
            :for="name"
            class="text-sm text-text cursor-pointer select-none">
            <slot />
        </label>
        
    </div>
</template>