<script setup>
    import NavLink from "../Components/NavLink.vue"
    import { Button } from '@/components/ui/button'
    import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet'
    import Logo from '../Components/Logo.vue'
    import { Home, Menu, HandHelping , Telescope } from 'lucide-vue-next'
    import { ref, onMounted, onUnmounted } from 'vue';

    const theme = ref(document.body.getAttribute('data-theme'));

    const updateThemeStatus = () => {
        theme.value = document.body.getAttribute('data-theme');
    };

    onMounted(() => {
        // create a MutationObserver to track attribute changes
        const observer = new MutationObserver(updateThemeStatus);

        // start observing the body for attribute changes
        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['data-theme'],
        });

        // clean up observer when the component is unmounted
        onUnmounted(() => {
            observer.disconnect();
        });
    });
</script>

<template>
    <Sheet>
        <!-- button to open mobile sidebar -->
        <SheetTrigger as-child>
            <Button variant="outline" size="icon" class="shrink-0 md:hidden">
                <Menu class="h-5 w-5" />
                <span class="sr-only">Toggle navigation menu</span>
            </Button>
        </SheetTrigger>

        <!-- mobile sidebar contents -->
        <SheetContent side="left" class="flex h-full max-h-screen flex-col gap-2 p-0">

            <!-- logo section (fixed at the top) -->
            <div class="flex items-center justify-center h-14 border-b sticky top-0">
                <Link :href="route('home')">
                    <Logo 
                        class="h-11 w-11"
                        :fill="theme === 'dark' ? '#FFFFFF' : '#09090B'"
                    />
                </Link>
            </div>

            <nav class="flex-1 px-4 overflow-y-auto text-lg font-medium">

                <!-- nav links -->
                <NavLink routeName="home" componentName="Home" size="sm">
                    <Home class="h-5 w-5" />
                    Home
                </NavLink>
                
                <NavLink routeName="explore" componentName="Explore" size="sm">
                    <Telescope class="h-5 w-5" />
                    Explore
                </NavLink>

                <NavLink routeName="my-rentals" componentName="MyRentals" size="sm">
                    <HandHelping  class="h-5 w-5" />
                    My Rentals
                </NavLink>
            </nav>

            <!-- sidebar footer -->
            <div class="p-4 sticky bottom-0 border-t">
                <Button size="sm" class="w-full">Create Listing</Button>
            </div>

        </SheetContent>
    </Sheet>
</template>