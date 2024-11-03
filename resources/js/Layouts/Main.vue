<script setup>
    import { switchTheme } from '../theme';
</script>

<template>
    <header>
        <nav class="p-6 mx-auto max-w-screen-lg flex items-center justify-between">
            <Link :href="route('home')" class=" font-semibold text-xl">Lend<span class=" text-primary">Works</span></Link>
            
            <div>
                <button 
                @click="switchTheme">
                    <i class="fa-solid fa-circle-half-stroke"></i>
                </button>
            </div>
        </nav>
    </header>

    <main class="p-6 mx-auto max-w-screen-lg">
        <slot />
    </main>
</template>
