<script setup>
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { Button } from "@/components/ui/button";
import Logo from "./Logo.vue";
import { Menu } from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";
import {
    LayoutDashboard,
    Users,
    PackageSearch,
    ClipboardList,
    Receipt,
} from "lucide-vue-next";

const theme = ref(document.body.getAttribute("data-theme"));

const updateThemeStatus = () => {
    theme.value = document.body.getAttribute("data-theme");
};

onMounted(() => {
    const observer = new MutationObserver(updateThemeStatus);
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ["data-theme"],
    });
    onUnmounted(() => {
        observer.disconnect();
    });
});

const navigation = [
    { name: "Dashboard", href: route("admin.dashboard"), component: "Admin/Dashboard", icon: LayoutDashboard },
    { name: "Users", href: route("admin.users"), component: "Admin/Users", icon: Users },
    { name: "Listings", href: route("admin.listings"), component: "Admin/Listings", icon: PackageSearch },
    { name: "Rental Transactions", href: route("admin.rental-transactions"), component: "Admin/RentalTransactions", icon: ClipboardList },
    { name: "Payments", href: route("admin.payments"), component: "Admin/Payments/Index", icon: Receipt },
];
</script>

<template>
    <Sheet>
        <SheetTrigger asChild>
            <Button variant="outline" size="icon" class="shrink-0 md:hidden">
                <Menu class="w-5 h-5" />
                <span class="sr-only">Toggle Menu</span>
            </Button>
        </SheetTrigger>
        <SheetContent side="left" class="flex flex-col h-full max-h-screen gap-2 p-0">
            <!-- logo section -->
            <div class="h-14 sticky top-0 flex items-center justify-center border-b">
                <Link :href="route('admin.dashboard')" class="flex items-center gap-2 font-semibold">
                    <Logo class="w-8 h-8" :fill="theme === 'dark' ? '#FFFFFF' : '#09090B'" />
                    <span>Admin Panel</span>
                </Link>
            </div>

            <!-- navigation -->
            <nav class="flex-1 px-4 overflow-y-auto text-lg font-medium">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-4 rounded-lg px-3 py-3 hover:text-primary text-sm',
                        $page.component === item.component
                            ? 'bg-muted text-primary'
                            : 'bg-none text-muted-foreground',
                    ]"
                >
                    <component :is="item.icon" class="w-5 h-5" />
                    {{ item.name }}
                </Link>
            </nav>
        </SheetContent>
    </Sheet>
</template>
