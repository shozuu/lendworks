<script setup>
    import Logo from '../Components/Logo.vue';  
    import { ref, onMounted, onUnmounted } from 'vue';

    const theme = ref(document.body.getAttribute('data-theme'))

    const updateThemeStatus = () => {
        theme.value = document.body.getAttribute('data-theme')
    }

    onMounted(() => {
        // create a MutationObserver to track attribute changes
        const observer = new MutationObserver(updateThemeStatus)

        // start observing the body for attribute changes
        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['data-theme'],
        })

        // clean up observer when the component is unmounted
        onUnmounted(() => {
            observer.disconnect()
        })
    }) 
</script>

<template>
    <div class="p-4">
        <div class="flex flex-col items-center my-20">
            <Link :href="route('home')" class=" h-14 shrink-0">
                <Logo 
                    :fill="theme === 'dark' ? '#FFFFFF' : '#09090B'"
                />
            </Link>
        </div>
        <slot />
    </div>
</template>
