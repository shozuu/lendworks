<script setup>
import { Link } from "@inertiajs/vue3";
import Logo from "./Logo.vue";
import { ref, onMounted, onUnmounted } from "vue";
import {
    LayoutDashboard,
    Users,
    PackageSearch,
    ClipboardList,
    Receipt,
    AlertTriangle,
    Settings,
    CreditCard,
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

/**
 * AdminSidebar Component Modifications
 * Last Modified: [Current Date]
 * 
 * Changes:
 * 1. Added new System navigation item
 * 2. Updated navigation array with system management link
 * 
 * Change Log:
 * 1. Removed separate Platform menu item
 * 2. Updated System menu item to be the single access point
 * 3. Simplified navigation structure
 * 4. Maintained consistent icon usage
 * 5. Updated route references
 */

const navigationGroups = {
    main: [
        { name: "Dashboard", href: route("admin.dashboard"), component: "Admin/Dashboard", icon: LayoutDashboard },
    ],
    management: [
        { name: "Users", href: route("admin.users"), component: "Admin/Users", icon: Users },
        { name: "Listings", href: route("admin.listings"), component: "Admin/Listings", icon: PackageSearch },
    ],
    transactions: [
        { name: "Rental Transactions", href: route("admin.rental-transactions"), component: "Admin/RentalTransactions", icon: ClipboardList },
        { name: "Revenue", href: route("admin.revenue"), component: "Admin/Revenue", icon: CreditCard },
        { name: "Payments", href: route("admin.payments"), component: "Admin/Payments/Index", icon: Receipt },
        { name: "Disputes", href: route("admin.disputes.index"), component: "Admin/Disputes", icon: AlertTriangle },
    ],
    system: [
        { name: "System", href: route("admin.system"), component: "Admin/System", icon: Settings },
        { name: "System Logs", href: route("admin.logs"), component: "Admin/Logs", icon: ClipboardList },
    ]
};
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
                <nav class="grid items-start gap-4 px-4 text-sm font-medium">
                    <!-- Main -->
                    <div class="space-y-1">
                        <Link
                            v-for="item in navigationGroups.main"
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
                    </div>

                    <!-- Management -->
                    <div class="space-y-1">
                        <div class="px-3 py-1 text-xs font-semibold text-muted-foreground">Management</div>
                        <Link
                            v-for="item in navigationGroups.management"
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
                    </div>

                    <!-- Transactions -->
                    <div class="space-y-1">
                        <div class="px-3 py-1 text-xs font-semibold text-muted-foreground">Transactions</div>
                        <Link
                            v-for="item in navigationGroups.transactions"
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
                    </div>

                    <!-- System -->
                    <div class="space-y-1">
                        <div class="px-3 py-1 text-xs font-semibold text-muted-foreground">System</div>
                        <Link
                            v-for="item in navigationGroups.system"
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
                    </div>
                </nav>
            </div>
        </div>
    </div>
</template>


