<script setup>
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { Button } from "@/components/ui/button";
import { Menu } from "lucide-vue-next";
import { Link } from "@inertiajs/vue3";

const navigation = [
	{ name: "Dashboard", href: route("admin.dashboard"), icon: "pie-chart" },
	{ name: "Users", href: route("admin.users"), icon: "users" },
	{ name: "Listings", href: route("admin.listings"), icon: "list" },
];
</script>

<template>
	<Sheet>
		<SheetTrigger asChild>
			<Button variant="ghost" size="icon" class="md:hidden">
				<Menu class="h-6 w-6" />
				<span class="sr-only">Toggle Menu</span>
			</Button>
		</SheetTrigger>
		<SheetContent side="left" class="w-72">
			<div class="px-1 py-6 space-y-4">
				<div class="mb-4">
					<h2 class="mb-2 px-4 text-lg font-semibold tracking-tight">Admin Menu</h2>
				</div>
				<nav class="grid gap-1">
					<Link
						v-for="item in navigation"
						:key="item.name"
						:href="item.href"
						:class="[
							'flex items-center gap-3 rounded-lg px-3 py-2 transition-all hover:text-primary text-sm',
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
		</SheetContent>
	</Sheet>
</template>
