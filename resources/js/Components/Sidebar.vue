<script setup>
    import NavLink from "../Components/NavLink.vue"
    import { Button } from '@/components/ui/button'
    import Logo from '../Components/Logo.vue'
    import { Bell, Home, HandHelping , Telescope } from 'lucide-vue-next'
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
    <!-- sidebar (visible on md and up) -->
    <div class="hidden border-r bg-muted/40 md:block">
        <div class="flex h-full max-h-screen flex-col gap-2">
                
            <!-- sidebar header -->
            <div class="flex h-14 items-center border-b px-4 lg:h-[60px] lg:px-6">
                <Link :href="route('home')" class="flex items-center gap-2 font-semibold">
                    <Logo 
                        class="h-8 w-8"
                        :fill="theme === 'dark' ? '#FFFFFF' : '#09090B'"
                    />
                    <span>LendWorks</span>
                </Link>
                <Button variant="outline" size="icon" class="ml-auto h-8 w-8">
                    <Bell class="h-4 w-4" />
                    <span class="sr-only">Toggle notifications</span>
                </Button>
            </div>

            <!-- sidebar navigation -->
            <div class="flex-1">
                <nav class="grid items-start px-2 text-sm font-medium lg:px-4">

                    <!-- nav links -->
                    <NavLink routeName="home" componentName="Home">
                        <Home class="h-5 w-5" />
                        Home
                    </NavLink>

                    <NavLink routeName="explore" componentName="Explore">
                        <Telescope class="h-5 w-5" />
                        Explore
                    </NavLink>

                    <NavLink routeName="my-rentals" componentName="MyRentals">
                        <HandHelping  class="h-5 w-5" />
                        My Rentals
                    </NavLink>
    
                </nav>
            </div>

            <!-- sidebar footer -->
            <div class="mt-auto p-4">
                <Button size="sm" class="w-full">Create Listing</Button>
            </div>
        </div>
    </div>
</template>