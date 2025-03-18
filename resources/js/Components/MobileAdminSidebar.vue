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
    DollarSign,
    AlertCircle,
    FileText,
    Settings
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
        { name: "Revenue", href: route("admin.revenue"), component: "Admin/Revenue", icon: DollarSign },
        { name: "Payments", href: route("admin.payments"), component: "Admin/PaymentRequests", icon: Receipt },
        { name: "Disputes", href: route("admin.disputes"), component: "Admin/Disputes", icon: AlertCircle },
    ],
    system: [
        { name: "System", href: route("admin.system"), component: "Admin/System", icon: Settings },
        { name: "System Logs", href: route("admin.logs"), component: "Admin/Logs", icon: FileText },
    ]
};
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
            <nav class="flex-1 px-4 overflow-y-auto">
                <!-- Main -->
                <div class="space-y-1 mb-4">
                    <Link
                        v-for="item in navigationGroups.main"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-4 rounded-lg px-3 py-2 hover:text-primary text-sm',
                            $page.component === item.component
                                ? 'bg-muted text-primary'
                                : 'bg-none text-muted-foreground',
                        ]"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        {{ item.name }}
                    </Link>
                </div>

                <!-- Management -->
                <div class="space-y-1 mb-4">
                    <div class="px-3 py-1 text-xs font-semibold text-muted-foreground">
                        Management
                    </div>
                    <Link
                        v-for="item in navigationGroups.management"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-4 rounded-lg px-3 py-2 hover:text-primary text-sm',
                            $page.component === item.component
                                ? 'bg-muted text-primary'
                                : 'bg-none text-muted-foreground',
                        ]"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        {{ item.name }}
                    </Link>
                </div>

                <!-- Transactions -->
                <div class="space-y-1 mb-4">
                    <div class="px-3 py-1 text-xs font-semibold text-muted-foreground">
                        Transactions
                    </div>
                    <Link
                        v-for="item in navigationGroups.transactions"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-4 rounded-lg px-3 py-2 hover:text-primary text-sm',
                            $page.component === item.component
                                ? 'bg-muted text-primary'
                                : 'bg-none text-muted-foreground',
                        ]"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        {{ item.name }}
                    </Link>
                </div>

                <!-- System -->
                <div class="space-y-1">
                    <div class="px-3 py-1 text-xs font-semibold text-muted-foreground">
                        System
                    </div>
                    <Link
                        v-for="item in navigationGroups.system"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-4 rounded-lg px-3 py-2 hover:text-primary text-sm',
                            $page.component === item.component
                                ? 'bg-muted text-primary'
                                : 'bg-none text-muted-foreground',
                        ]"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        {{ item.name }}
                    </Link>
                </div>
            </nav>
        </SheetContent>
    </Sheet>
</template>
