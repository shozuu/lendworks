<script setup>
    defineProps({
        routeName: String,
        label: String
    })
</script>

<template>
    <Link :href="route(routeName)" class="text-accent hover:text-accent-hover underline">{{ label }}</Link>
</template>