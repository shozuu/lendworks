<script setup>
    defineProps({
        routeName: String,
        componentName: String
    });
</script>

<template>
    <Link 
        :href="route(routeName)"
        :class="[
            'text-sm',
            'hover:text-primary', 
            $page.component === componentName ? 'text-primary' : 'text-text'
        ]">
        <slot/>
    </Link>
</template>
