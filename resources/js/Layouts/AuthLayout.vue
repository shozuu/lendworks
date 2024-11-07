<script setup>
    import Logo from '../Components/Logo.vue';   
</script>

<template>
    <div class="flex flex-col items-center my-20">
        <div class=" h-14 shrink-0">
            <Logo :wrenchColor="'var(--wrench-color)'" :handColor="'var(--hand-color)'" />
        </div>
    </div>
    <slot />
</template>
