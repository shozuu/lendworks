<script setup>
import { Button } from "@/components/ui/button";
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuLabel,
	DropdownMenuSeparator,
	DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { CircleUser } from "lucide-vue-next";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const user = computed(() => usePage().props.auth.user);
</script>

<template>
	<!-- auth -->
	<DropdownMenu v-if="user">
		<DropdownMenuTrigger as-child>
			<Button variant="ghost" size="icon">
				<img
					src="https://picsum.photos/200"
					alt="Profile Picture"
					class="h-5 w-5 rounded-full"
				/>
				<span class="sr-only">Toggle user menu</span>
			</Button>
		</DropdownMenuTrigger>

		<DropdownMenuContent class="min-w-40" align="end">
			<DropdownMenuLabel
				>{{ user.name }}
				<p class="text-xs mt-1 leading-none text-muted-foreground">{{ user.email }}</p>
			</DropdownMenuLabel>
			<DropdownMenuSeparator />
			<DropdownMenuItem as-child routeName="profile.edit">Profile </DropdownMenuItem>
			<DropdownMenuItem as-child routeName="home">Test Link </DropdownMenuItem>
			<DropdownMenuSeparator />
			<DropdownMenuItem as-child routeName="logout" method="post"
				>Logout
			</DropdownMenuItem>
		</DropdownMenuContent>
	</DropdownMenu>

	<!-- guest -->
	<DropdownMenu v-else>
		<DropdownMenuTrigger as-child>
			<Button variant="ghost" size="icon">
				<CircleUser class="h-9 w-9" />
				<span class="sr-only">Toggle user menu</span>
			</Button>
		</DropdownMenuTrigger>

		<DropdownMenuContent align="end">
			<DropdownMenuItem as-child routeName="login">Log In </DropdownMenuItem>

			<DropdownMenuItem as-child routeName="register">Register </DropdownMenuItem>
		</DropdownMenuContent>
	</DropdownMenu>
</template>