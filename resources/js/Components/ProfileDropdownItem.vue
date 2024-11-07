<script setup>
    defineProps({
        routeName: String,
        componentName: String
    });
</script>

<template>
    <Link 
        :href="route(routeName)"
        class="block w-full px-4 py-2 text-sm rounded-md hover:bg-secondary text-left"><slot />
    </Link>
</template>