<script setup>
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { SunMoon, Search } from "lucide-vue-next";
import Sidebar from "../Components/Sidebar.vue";
import MobileSidebar from "../Components/MobileSidebar.vue";
import UserDropdownMenu from "../Components/UserDropdownMenu.vue";
import { switchTheme } from "../theme";
</script>

<template>
	<!-- layout wrapper -->
	<div class="grid min-h-screen w-full md:grid-cols-[220px_1fr] lg:grid-cols-[240px_1fr]">
		<!-- main sidebar -->
		<Sidebar />

		<!-- main content (1fr)-->
		<div class="flex flex-col">
			<!-- header -->
			<header class="sticky top-0 z-10 border-b bg-background">
				<div
					class="mx-auto flex h-14 max-w-screen-2xl items-center gap-4 px-4 lg:h-[60px] lg:px-6"
				>
					<!-- Mobile Sidebar -->
					<MobileSidebar />

					<!-- Search Bar -->
					<div class="flex-1">
						<form>
							<div class="relative">
								<Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
								<Input
									type="search"
									placeholder="Search anything..."
									class="w-full pl-8 appearance-none bg-muted md:w-2/3 lg:w-1/3"
								/>
							</div>
						</form>
					</div>

					<!-- Header Actions -->
					<div class="flex items-center gap-1">
						<!-- Theme Toggle -->
						<Button @click="switchTheme()" variant="ghost" size="icon">
							<SunMoon class="h-9 w-9" />
							<span class="sr-only">Toggle dark mode</span>
						</Button>

						<!-- User Dropdown Menu -->
						<UserDropdownMenu />
					</div>
				</div>
			</header>

			<!-- main content -->
			<main class="w-full px-4 py-6 mx-auto lg:max-w-screen-2xl lg:px-8 lg:py-8">
				<!-- slot content -->
				<slot />
			</main>
		</div>
	</div>
</template>
