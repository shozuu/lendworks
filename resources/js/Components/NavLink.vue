<script setup>
    defineProps({
        routeName: String,
        componentName: String,
        size: String,
    });
</script>

<template>
    <!-- for sm screen -->
    <Link 
        v-if="size === 'sm'"
        :href="route(routeName)"
        :class="[
            'flex items-center gap-4 rounded-xl px-3 py-2 hover:text-foreground', 
            $page.component === componentName ? 'bg-muted text-foreground' : 'bg-none text-muted-foreground'
        ]">
        <slot/>
    </Link>

    <!-- for md-lg screens -->
    <Link 
        v-else
        :href="route(routeName)"
        :class="[
            'flex items-center gap-3 rounded-lg px-3 py-2 transition-all hover:text-primary', 
            $page.component === componentName ? 'bg-muted text-primary' : ' bg-none text-muted-foreground'
        ]">
        <slot/>
    </Link>
</template>
