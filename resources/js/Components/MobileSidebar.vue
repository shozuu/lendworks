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
        <SheetContent side="left" class="flex h-full max-h-screen flex-col gap-2">
            <nav class="grid gap-2 text-lg font-medium">
                                
                <!-- logo -->
                <Link :href="route('home')" class="px-3">
                    <Logo 
                        class="h-10 w-10"
                        :fill="theme === 'dark' ? '#FFFFFF' : '#09090B'"
                    />
                </Link>

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
            <div class="mt-auto">
                <Button size="sm" class="w-full">Create Listing</Button>
            </div>

        </SheetContent>
    </Sheet>
</template>