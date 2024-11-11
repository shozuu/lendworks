<script setup>
    defineProps({
        routeName: String,
        label: String
    })
</script>

<template>
    <Link :href="route(routeName)" class="text-foreground hover:text-primary underline-offset-4 hover:underline">{{ label }}</Link>
</template>