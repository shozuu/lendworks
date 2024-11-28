<script setup>
import { Button } from "@/components/ui/button";
import { SunMoon, Search } from "lucide-vue-next";
import Sidebar from "../Components/Sidebar.vue";
import MobileSidebar from "../Components/MobileSidebar.vue";
import UserDropdownMenu from "../Components/UserDropdownMenu.vue";
import { switchTheme } from "../theme";
import { useForm, router } from "@inertiajs/vue3";
import InputField from "@/Components/InputField.vue";

// use route.params() to 'stack' search query parameters coming from different components and pass them as one parameter

const params = route().params;
// searchTerm is automatically passed from controller->view->layout

const props = defineProps({ searchTerm: String });

const form = useForm({
	search: props.searchTerm,
});

const search = () => {
	router.get(route("explore"), {
		search: form.search,
	});
};
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
					class="mx-auto flex h-14 max-w-screen-2xl items-center gap-4 px-6 lg:h-[60px]"
				>
					<!-- Mobile Sidebar -->
					<MobileSidebar />

					<!-- Search Bar -->
					<div class="flex-1">
						<form @submit.prevent="search">
							<div class="relative">
								<Search
									class="absolute left-2.5 top-3 h-4 w-4 text-muted-foreground z-10"
								/>
								<InputField
									type="search"
									placeholder="Search anything..."
									bg="bg-muted"
									addedClass="m-0 py-2 w-full pl-8 appearance-none bg-muted md:w-2/3 lg:w-1/3 h-10"
									v-model="form.search"
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
			<main class="flex-1 w-full p-6 mx-auto lg:max-w-screen-2xl lg:p-8">
				<!-- slot content -->
				<slot />
			</main>
		</div>
	</div>
</template>