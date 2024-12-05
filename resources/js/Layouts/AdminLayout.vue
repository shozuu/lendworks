<script setup>
import { ref } from "vue";
import {
	Users,
	CreditCard,
	ClipboardList,
	LayoutDashboard,
	LogOut,
    Package
} from "lucide-vue-next";
import { Link, usePage, router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";

// Get the current page from Inertia
const page = usePage();

// Updated isActive function using usePage
const isActive = (componentName) => {
	return page.component === componentName;
};

const navigation = [
	{
		name: "Dashboard",
		href: route("admin.dashboard"),
		icon: LayoutDashboard,
		component: "Admin/Dashboard",
	},
    {
        name: "Listings",
        href: route("admin.listings"),
        icon: Package,
        component: "Admin/Listings",
    },
    {
        name: "Payments",
        href: route("admin.payments"), 
        icon: CreditCard,
        component: "Admin/Payments",
    }
];

const signOut = () => {
	router.post(route("logout"));
};
</script>

<template>
	<div class="bg-background min-h-screen">
		<!-- Navigation -->
		<nav class="border-b">
			<div class="max-w-7xl sm:px-6 lg:px-8 px-4 mx-auto">
				<div class="flex items-center justify-between h-16">
					<div class="flex items-center">
						<div class="text-xl font-bold">Admin Panel</div>
						<div class="flex items-baseline ml-10 space-x-4">
							<Link
								v-for="item in navigation"
								:key="item.name"
								:href="item.href"
								:class="[
									route().current(item.href.split('.')[1])
										? 'bg-muted text-foreground'
										: 'text-muted-foreground hover:bg-muted hover:text-foreground',
									'rounded-md px-3 py-2 text-sm font-medium'
								]"
							>
								{{ item.name }}
							</Link>
						</div>
					</div>
				</div>
			</div>
		</nav>

		<!-- Page Content -->
		<main>
			<div class="max-w-7xl sm:px-6 lg:px-8 py-6 mx-auto">
				<slot />
			</div>
		</main>
	</div>
</template>
