<script setup>
import { Link } from "@inertiajs/vue3";

/**
 * AdminSidebar Component Modifications
 * Last Modified: [Current Date]
 * 
 * Changes:
 * 1. Added new System navigation item
 * 2. Updated navigation array with system management link
 */

const navigation = [
    { name: "Dashboard", href: route("admin.dashboard"), icon: "pie-chart" },
    { name: "Users", href: route("admin.users"), icon: "users" },
    { name: "Listings", href: route("admin.listings"), icon: "list" },
    { 
        name: "Rental Transactions", 
        href: route("admin.rental-transactions"), 
        icon: "exchange" 
    },
    { 
        name: "System", 
        href: route("admin.system"), 
        icon: "gear",  // Using FontAwesome gear icon
    },
];
</script>

<template>
    <div class="bg-card md:block hidden border-r">
        <div class="flex flex-col h-full max-h-screen gap-2">
            <div class="flex h-[60px] items-center border-b px-6">
                <Link
                    :href="route('admin.dashboard')"
                    class="flex items-center gap-2 font-semibold"
                >
                    <span>Admin Panel</span>
                </Link>
            </div>
            <div class="flex-1 py-2 overflow-auto">
                <nav class="grid items-start px-4 text-sm font-medium">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-3 rounded-lg px-3 py-2 transition-all hover:text-primary',
                            $page.url.startsWith(item.href)
                                ? 'bg-muted text-primary'
                                : 'text-muted-foreground',
                        ]"
                    >
                        <i class="fa-solid" :class="`fa-${item.icon}`"></i>
                        {{ item.name }}
                    </Link>
                </nav>
            </div>
        </div>
    </div>
</template>
