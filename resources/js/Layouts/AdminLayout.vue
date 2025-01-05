<script setup>
import { Button } from "@/components/ui/button";
import { SunMoon } from "lucide-vue-next";
import { switchTheme } from "../theme";
import MobileAdminSidebar from "@/Components/MobileAdminSidebar.vue";
import AdminSidebar from "@/Components/AdminSidebar.vue";
</script>

<template>
	<div class="grid min-h-screen w-full md:grid-cols-[220px_1fr]">
		<!-- Sidebar -->
		<AdminSidebar />

		<!-- Main Content -->
		<div class="flex flex-col">
			<!-- Header -->
			<header
				class="flex h-14 lg:h-[60px] items-center gap-4 border-b bg-background px-6"
			>
				<!-- Mobile Menu -->
				<MobileAdminSidebar />

				<div class="flex flex-1 items-center justify-end gap-4">
					<Button @click="switchTheme()" variant="ghost" size="icon">
						<SunMoon class="h-5 w-5" />
						<span class="sr-only">Toggle theme</span>
					</Button>
				</div>
			</header>

			<!-- Main Content Area -->
			<main class="flex-1 overflow-auto p-6">
				<slot />
			</main>
		</div>
	</div>
</template>
