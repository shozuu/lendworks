<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import debounce from "lodash/debounce";
import { ref, watch } from "vue";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	users: Object,
	filters: {
		type: Object,
		default: () => ({
			search: "",
			status: "all",
			sortBy: "latest",
		}),
	},
});

const getStatusBadge = (status) => {
	const badges = {
		active: "success",
		suspended: "destructive",
	};
	return badges[status] || "default";
};

const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "all");
const sortBy = ref(props.filters?.sortBy ?? "latest");
const showDialog = ref(false);
const selectedUser = ref(null);
const action = ref(null);

const updateSearch = debounce((value) => {
	router.get(
		route("admin.users"),
		{ ...props.filters, search: value },
		{ preserveState: true, preserveScroll: true, replace: true }
	);
}, 300);

// Immediate update for status and sort
const updateFilters = (newFilters) => {
	router.get(
		route("admin.users"),
		{ ...props.filters, ...newFilters },
		{ preserveState: true, preserveScroll: true }
	);
};

// Watch for changes
watch(search, (newVal) => {
	updateSearch(newVal);
});

watch(status, (newVal) => {
	updateFilters({ status: newVal });
});

// Update watch handler for sort
watch(sortBy, (newVal) => {
	updateFilters({ sortBy: newVal }); // Changed from 'sort' to 'sortBy'
});

const confirmAction = (user, actionType) => {
	selectedUser.value = user;
	action.value = actionType;
	showDialog.value = true;
};

const handleAction = () => {
	const routeName =
		action.value === "suspend" ? "admin.users.suspend" : "admin.users.activate";
	router.patch(
		route(routeName, selectedUser.value.id),
		{},
		{
			onSuccess: () => (showDialog.value = false),
		}
	);
};
</script>

<template>
	<Head title="| Admin - Users" />

	<div class="space-y-6">
		<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
			<h2 class="text-2xl font-semibold tracking-tight">Manage Users</h2>

			<div class="flex flex-wrap items-center gap-4">
				<!-- Search -->
				<div class="relative w-full sm:w-[200px]">
					<Input type="search" placeholder="Search users..." v-model="search" />
				</div>

				<!-- Status Filter -->
				<Select v-model="status" defaultValue="all">
					<SelectTrigger class="w-full sm:w-[140px]">
						<SelectValue placeholder="Filter by status" />
					</SelectTrigger>
					<SelectContent>
						<SelectItem value="all">All Status</SelectItem>
						<SelectItem value="active">Active</SelectItem>
						<SelectItem value="suspended">Suspended</SelectItem>
					</SelectContent>
				</Select>

				<!-- Sort -->
				<Select v-model="sortBy" defaultValue="latest">
					<!-- Added defaultValue -->
					<SelectTrigger class="w-full sm:w-[140px]">
						<SelectValue placeholder="Sort by" />
					</SelectTrigger>
					<SelectContent>
						<SelectItem value="latest">Latest</SelectItem>
						<SelectItem value="oldest">Oldest</SelectItem>
						<SelectItem value="name">Name</SelectItem>
						<SelectItem value="listings">Most Listings</SelectItem>
					</SelectContent>
				</Select>
			</div>
		</div>

		<!-- Users List -->
		<div v-if="users.data.length" class="space-y-4">
			<Card v-for="user in users.data" :key="user.id" class="p-6">
				<div class="flex items-center justify-between">
					<div class="space-y-1">
						<div class="flex items-center gap-2">
							<h3 class="font-semibold">{{ user.name }}</h3>
							<Badge :variant="getStatusBadge(user.status)">
								{{ user.status }}
							</Badge>
						</div>
						<p class="text-sm text-muted-foreground">{{ user.email }}</p>
						<p class="text-xs text-muted-foreground">
							Member since {{ new Date(user.created_at).toLocaleDateString() }}
						</p>
						<p class="text-sm">Total Listings: {{ user.listings_count }}</p>
					</div>

					<div class="flex gap-2">
						<Button
							v-if="user.status === 'active'"
							variant="destructive"
							size="sm"
							@click="confirmAction(user, 'suspend')"
						>
							Suspend
						</Button>
						<Button
							v-if="user.status === 'suspended'"
							variant="default"
							size="sm"
							@click="confirmAction(user, 'activate')"
						>
							Activate
						</Button>
						<Button
							variant="outline"
							size="sm"
							@click="router.get(route('admin.users.show', user.id))"
						>
							View Details
						</Button>
					</div>
				</div>
			</Card>

			<PaginationLinks :paginator="users" />
		</div>
		<div v-else class="text-muted-foreground py-10 text-center">No users found</div>

		<!-- Confirmation Dialog -->
		<ConfirmDialog
			:show="showDialog"
			:title="action === 'suspend' ? 'Suspend User' : 'Activate User'"
			:description="`Are you sure you want to ${
				action === 'suspend' ? 'suspend' : 'activate'
			} ${selectedUser?.name}?`"
			confirmLabel="Confirm"
			confirmVariant="destructive"
			@update:show="showDialog = $event"
			@confirm="handleAction"
			@cancel="showDialog = false"
		/>
	</div>
</template>
