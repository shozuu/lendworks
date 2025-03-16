<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Separator } from "@/components/ui/separator";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectLabel,
	SelectValue,
} from "@/components/ui/select";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import debounce from "lodash/debounce";
import { ref, watch } from "vue";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import { formatDate } from "@/lib/formatters";
import { Download } from "lucide-vue-next";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	users: Object,
	filters: {
		type: Object,
		default: () => ({
			search: "",
			status: "all",
			sortBy: "latest",
			verified: "all",
		}),
	},
	userCounts: {
		type: Object,
		required: true
	}
});

const getStatusBadge = (status) => {
	const badges = {
		active: { variant: "success", label: "Active" },
		suspended: { variant: "destructive", label: "Suspended" },
	};
	return badges[status] || { variant: "default", label: status };
};

const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "all");
const sortBy = ref(props.filters?.sortBy ?? "latest");
const verified = ref(props.filters?.verified ?? "all");
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

watch(verified, (newVal) => {
	updateFilters({ verified: newVal });
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

const exportToCSV = () => {
    const headers = [
        'Name',
        'Email',
        'Status',
        'Verification',
        'Listings Count',
        'Join Date'
    ].join(',');

    const rows = props.users.data.map(user => [
        `"${user.name}"`,
        `"${user.email}"`,
        user.status,
        user.email_verified_at ? 'Verified' : 'Unverified',
        user.listings_count,
        formatDate(user.created_at)
    ].join(','));

    const csv = [headers, ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `users-${formatDate(new Date())}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
};
</script>

<template>
	<Head title="| Admin - Users" />

	<div class="space-y-6">
		<div class="flex flex-col gap-4">
			<div class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-2">
				<h2 class="text-2xl font-semibold tracking-tight">Manage Users</h2>
				<div class="flex flex-wrap gap-2">
					<Badge variant="outline">Total: {{ userCounts.total }}</Badge>
					<Badge variant="success">Active: {{ userCounts.active }}</Badge>
					<Badge variant="destructive">Suspended: {{ userCounts.suspended }}</Badge>
				</div>
			</div>

			<div class="sm:flex-row flex flex-col gap-3">
				<div class="flex-1">
					<Input type="search" placeholder="Search users..." v-model="search" class="max-w-xs" />
				</div>
				<div class="flex flex-wrap gap-3">
					<!-- Status Filter -->
					<Select v-model="status" defaultValue="all">
						<SelectTrigger class="w-full sm:w-[180px]">
							<SelectValue placeholder="Filter by status" />
						</SelectTrigger>
						<SelectContent>
							<SelectLabel class="p-1 text-center">Filter Status</SelectLabel>
							<Separator class="my-2" />
							<SelectItem value="all" class="flex items-center justify-between">
							<span>All Status</span>
							<span class="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
								{{ userCounts.total }}
							</span>
						</SelectItem>
							<SelectItem value="active" class="flex items-center justify-between">
							<span>Active</span>
							<span class="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
								{{ userCounts.active }}
							</span>
						</SelectItem>
							<SelectItem value="suspended" class="flex items-center justify-between">
							<span>Suspended</span>
							<span class="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
								{{ userCounts.suspended }}
							</span>
						</SelectItem>
					</SelectContent>
				</Select>

				 <!-- Verification Filter -->
                <Select v-model="verified" defaultValue="all">
                    <SelectTrigger class="w-full sm:w-[180px]">
                        <SelectValue placeholder="Verification" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectLabel class="p-1 text-center">Verification Status</SelectLabel>
                        <Separator class="my-2" />
                        <SelectItem value="all" class="flex items-center justify-between">
                            <span>All Users</span>
                            <span class="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
                                {{ userCounts.total }}
                            </span>
                        </SelectItem>
                        <SelectItem value="verified" class="flex items-center justify-between">
                            <span>Verified</span>
                            <span class="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
                                {{ userCounts.verified }}
                            </span>
                        </SelectItem>
                        <SelectItem value="unverified" class="flex items-center justify-between">
                            <span>Unverified</span>
                            <span class="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
                                {{ userCounts.unverified }}
                            </span>
                        </SelectItem>
	                    </SelectContent>
	                </Select>

					<!-- Sort -->
					<Select v-model="sortBy" defaultValue="latest">
						<!-- Added defaultValue -->
						<SelectTrigger class="w-full sm:w-[140px]">
							<SelectValue placeholder="Sort by" />
						</SelectTrigger>
						<SelectContent>
							<SelectLabel class="p-1 text-center">Sort Users</SelectLabel>
							<Separator class="my-2" />
							<SelectItem value="latest">Latest</SelectItem>
							<SelectItem value="oldest">Oldest</SelectItem>
							<SelectItem value="name">Name</SelectItem>
							<SelectItem value="listings">Most Listings</SelectItem>
						</SelectContent>
					</Select>

					<Button @click="exportToCSV" variant="outline" class="gap-2">
						<Download class="h-4 w-4" />
						Export CSV
					</Button>
				</div>
			</div>
		</div>

		<!-- Users List -->
		<div v-if="users.data.length" class="space-y-4">
			<Card
				v-for="user in users.data"
				:key="user.id"
				class="sm:p-6 hover:ring-1 hover:ring-primary/20 group p-4 transition-all cursor-pointer"
			>
				<Link :href="route('admin.users.show', user.id)" class="block">
					<div class="sm:flex-row sm:items-center flex flex-col gap-4">
						<!-- User Info -->
						<div class="flex-1 min-w-0 space-y-2">
							<div class="flex flex-wrap items-center gap-2">
								<h3
									class="group-hover:text-primary group-hover:underline font-semibold truncate transition-colors"
								>
									{{ user.name }}
								</h3>
								<Badge :variant="getStatusBadge(user.status).variant">
									{{ getStatusBadge(user.status).label }}
								</Badge>
								<Badge :variant="user.email_verified_at ? 'success' : 'secondary'">
                                    {{ user.email_verified_at ? 'Verified' : 'Unverified' }}
                                </Badge>
							</div>
							<div class="sm:text-sm text-muted-foreground space-y-1 text-xs">
								<p class="truncate">{{ user.email }}</p>
								<p>Member since {{ formatDate(user.created_at) }}</p>
								<p>Total Listings: {{ user.listings_count }}</p>
							</div>
						</div>

						<!-- Actions -->
						<div class="sm:justify-end flex flex-wrap gap-2">
							<Button
								v-if="user.status === 'active'"
								variant="destructive"
								size="sm"
								@click.prevent="confirmAction(user, 'suspend')"
							>
								Suspend
							</Button>
							<Button
								v-if="user.status === 'suspended'"
								variant="default"
								size="sm"
								@click.prevent="confirmAction(user, 'activate')"
							>
								Activate
							</Button>
						</div>
					</div>
				</Link>
			</Card>

			<PaginationLinks :paginator="users" />
		</div>
		<div v-else class="text-muted-foreground py-10 text-center">No users found</div>

		<!-- Confirmation Dialog -->
		<ConfirmDialog
			:show="showDialog"
			:title="action === 'suspend' ? 'Suspend User Account' : 'Activate User Account'"
			:description="
				action === 'suspend'
					? `Are you sure you want to suspend ${selectedUser?.name}? This will also mark all their listings as unavailable.`
					: `Are you sure you want to activate ${selectedUser?.name}'s account? They will be able to resume normal account activities.`
			"
			:confirmLabel="action === 'suspend' ? 'Suspend User' : 'Activate User'"
			:confirmVariant="action === 'suspend' ? 'destructive' : 'default'"
			@update:show="showDialog = $event"
			@confirm="handleAction"
			@cancel="showDialog = false"
		/>
	</div>
</template>

<style scoped>
/* Add these styles for consistent alignment */
:deep(.select-content) {
    min-width: 200px;
}

:deep(.select-item) {
    padding-right: 1rem;
}
</style>
