<script setup>
import { Link } from "@inertiajs/vue3";
import Logo from "./Logo.vue";
import { ref, onMounted, onUnmounted } from "vue";
import {
    LayoutDashboard,
    Users,
    PackageSearch,
    ClipboardList
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
];
</script>

<template>
    <div class="md:block sticky top-0 hidden h-screen border-r">
        <div class="flex flex-col h-full gap-2">
            <!-- sidebar header -->
            <div class="flex h-14 items-center border-b px-4 lg:h-[60px] lg:px-6 sticky top-0 z-10">
                <Link :href="route('admin.dashboard')" class="flex items-center gap-2 font-semibold">
                    <Logo class="w-8 h-8" :fill="theme === 'dark' ? '#FFFFFF' : '#09090B'" />
                    <span>Admin Panel</span>
                </Link>
            </div>

            <!-- sidebar navigation -->
            <div class="flex-1 overflow-y-auto">
                <nav class="grid items-start gap-1 px-4 text-sm font-medium">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-3 rounded-lg px-3 py-2 transition-all hover:text-primary',
                            $page.component === item.component
                                ? 'bg-muted text-primary'
                                : 'text-muted-foreground',
                        ]"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        {{ item.name }}
                    </Link>
                </nav>
            </div>
        </div>
    </div>
</template>


