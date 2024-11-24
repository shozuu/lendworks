<script setup>
import NavLink from "../Components/NavLink.vue";
import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import Logo from "../Components/Logo.vue";
import { Home, Menu, HandHelping, Telescope } from "lucide-vue-next";
import { ref, onMounted, onUnmounted } from "vue";

const theme = ref(document.body.getAttribute("data-theme"));

const updateThemeStatus = () => {
    theme.value = document.body.getAttribute("data-theme");
};

onMounted(() => {
    // create a MutationObserver to track attribute changes
    const observer = new MutationObserver(updateThemeStatus);

    // start observing the body for attribute changes
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ["data-theme"],
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
                <Menu class="w-5 h-5" />
                <span class="sr-only">Toggle navigation menu</span>
            </Button>
        </SheetTrigger>

        <!-- mobile sidebar contents -->
        <SheetContent
            side="left"
            class="flex flex-col h-full max-h-screen gap-2 p-0"
        >
            <!-- logo section (fixed at the top) -->
            <div
                class="sticky top-0 flex items-center justify-center border-b h-14"
            >
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
                    <Home class="w-5 h-5" />
                    Home
                </NavLink>

                <NavLink routeName="explore" componentName="Explore" size="sm">
                    <Telescope class="w-5 h-5" />
                    Explore
                </NavLink>

                <NavLink
                    routeName="my-rentals"
                    componentName="MyRentals"
                    size="sm"
                >
                    <HandHelping class="w-5 h-5" />
                    My Rentals
                </NavLink>
            </nav>

            <!-- sidebar footer -->
            <Link 
                :href="route('listing.create')"
                class="sticky bottom-0 p-4 border-t">
                <Button size="sm" class="w-full" variant="outline">
                    Create Listing
                </Button>
            </Link>
        </SheetContent>
    </Sheet>
</template>
