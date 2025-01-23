<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import AdminListingCard from "@/Components/AdminListingCard.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogDescription,
	DialogFooter,
} from "@/components/ui/dialog";
import { formatDate } from "@/lib/formatters";
import {
	Select,
	SelectTrigger,
	SelectValue,
	SelectContent,
	SelectItem,
	SelectLabel,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Separator } from "@/components/ui/separator";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	user: Object,
	rejectionReasons: {
		type: Array,
		required: true,
	},
	listingCounts: {
		type: Object,
		required: true,
	},
});

// Add handlers for approve/reject actions
const handleApprove = (listing) => {
	router.patch(route("admin.listings.approve", listing.id), {}, { preserveScroll: true });
};

const handleReject = (listing) => {
	router.patch(route("admin.listings.reject", listing.id), {}, { preserveScroll: true });
};

const showSuspendDialog = ref(false);
const showActivateDialog = ref(false);

const handleSuspend = () => {
	router.patch(
		route("admin.users.suspend", props.user.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => {
				showSuspendDialog.value = false;
			},
		}
	);
};

const handleActivate = () => {
	router.patch(
		route("admin.users.activate", props.user.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => {
				showActivateDialog.value = false;
			},
		}
	);
};

// Add new state for filters
const search = ref("");
const statusFilter = ref("all");
const sortBy = ref("latest");

// Computed property for filtered and sorted listings
const filteredListings = computed(() => {
	let filtered = [...props.user.listings];

	// Apply search filter
	if (search.value) {
		const searchTerm = search.value.toLowerCase();
		filtered = filtered.filter(
			(listing) =>
				listing.title.toLowerCase().includes(searchTerm) ||
				listing.desc.toLowerCase().includes(searchTerm)
		);
	}

	// Apply status filter
	if (statusFilter.value !== "all") {
		filtered = filtered.filter((listing) => listing.status === statusFilter.value);
	}

	// Apply sorting
	filtered.sort((a, b) => {
		switch (sortBy.value) {
			case "oldest":
				return new Date(a.created_at) - new Date(b.created_at);
			case "price-high":
				return b.price - a.price;
			case "price-low":
				return a.price - b.price;
			case "title":
				return a.title.localeCompare(b.title);
			default:
				// latest
				return new Date(b.created_at) - new Date(a.created_at);
		}
	});

	return filtered;
});
</script>

<template>
	<Head :title="`| Admin - User Details: ${user.name}`" />

	<div class="space-y-6">
		<!-- User Info Card -->
		<Card class="overflow-hidden">
			<CardHeader class="bg-card border-b">
				<div class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-4">
					<div class="space-y-1">
						<CardTitle class="sm:text-2xl text-xl">{{ user.name }}</CardTitle>
						<p class="text-muted-foreground text-sm">{{ user.email }}</p>
					</div>
					<Badge
						:variant="user.status === 'active' ? 'success' : 'destructive'"
						class="w-fit"
					>
						{{ user.status === "active" ? "Active" : "Suspended" }}
					</Badge>
				</div>
			</CardHeader>

			<CardContent class="p-6">
				<div class="grid gap-6">
					<!-- User Stats -->
					<div class="sm:grid-cols-4 grid grid-cols-2 gap-4">
						<div class="space-y-1">
							<p class="text-muted-foreground text-sm">Member Since</p>
							<p class="font-medium">{{ formatDate(user.created_at) }}</p>
						</div>
						<div class="space-y-1">
							<p class="text-muted-foreground text-sm">Total Listings</p>
							<p class="font-medium">{{ user.listings.length }}</p>
						</div>
					</div>

					<!-- Action Buttons -->
					<div class="flex flex-wrap gap-3">
						<Button
							v-if="user.status === 'active'"
							variant="destructive"
							@click="showSuspendDialog = true"
							class="sm:flex-none flex-1"
						>
							Suspend User
						</Button>
						<Button
							v-if="user.status === 'suspended'"
							variant="default"
							@click="showActivateDialog = true"
							class="sm:flex-none flex-1"
						>
							Activate User
						</Button>
					</div>
				</div>
			</CardContent>
		</Card>

		<!-- User's Listings -->
		<div class="space-y-4">
			<div class="flex flex-col gap-4">
				<!-- Header with counts -->
				<div class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-2">
					<h3 class="text-lg font-semibold">User's Listings</h3>
					<div class="flex flex-wrap gap-2">
						<Badge variant="outline"> Total: {{ listingCounts.total }} </Badge>
						<Badge variant="warning"> Pending: {{ listingCounts.pending }} </Badge>
						<Badge variant="success"> Approved: {{ listingCounts.approved }} </Badge>
						<Badge variant="destructive"> Rejected: {{ listingCounts.rejected }} </Badge>
						<Badge variant="destructive">
							Taken Down: {{ listingCounts.taken_down }}
						</Badge>
					</div>
				</div>

				<!-- Filters and Search -->
				<div class="sm:flex-row flex flex-col gap-3">
					<div class="flex-1">
						<Input v-model="search" placeholder="Search listings..." class="max-w-xs" />
					</div>
					<div class="flex flex-wrap gap-3">
						<Select v-model="statusFilter">
							<SelectTrigger class="w-[140px]">
								<SelectValue placeholder="Filter status" />
							</SelectTrigger>
							<SelectContent>
								<SelectLabel class="px-2 py-1.5">Filter Status</SelectLabel>
								<Separator class="my-2" />
								<SelectItem value="all">All Status</SelectItem>
								<SelectItem value="pending">Pending</SelectItem>
								<SelectItem value="approved">Approved</SelectItem>
								<SelectItem value="rejected">Rejected</SelectItem>
								<SelectItem value="taken_down">Taken Down</SelectItem>
							</SelectContent>
						</Select>

						<Select v-model="sortBy">
							<SelectTrigger class="w-[140px]">
								<SelectValue placeholder="Sort by" />
							</SelectTrigger>
							<SelectContent>
								<SelectLabel class="px-2 py-1.5">Sort Listings</SelectLabel>
								<Separator class="my-2" />
								<SelectItem value="latest">Latest</SelectItem>
								<SelectItem value="oldest">Oldest</SelectItem>
								<SelectItem value="title">Title</SelectItem>
								<SelectItem value="price-high">Price: High to Low</SelectItem>
								<SelectItem value="price-low">Price: Low to High</SelectItem>
							</SelectContent>
						</Select>
					</div>
				</div>
			</div>

			<!-- Listings Grid -->
			<div v-if="filteredListings.length" class="grid gap-4">
				<AdminListingCard
					v-for="listing in filteredListings"
					:key="listing.id"
					:listing="listing"
					:rejection-reasons="rejectionReasons"
					@approve="handleApprove"
					@reject="handleReject"
				/>
			</div>
			<div
				v-else-if="search || statusFilter !== 'all'"
				class="text-muted-foreground bg-card py-8 text-center border rounded-lg"
			>
				No listings match your filters.
			</div>
			<div
				v-else
				class="text-muted-foreground bg-card py-8 text-center border rounded-lg"
			>
				This user has no listings.
			</div>
		</div>

		<!-- Suspend Dialog -->
		<Dialog v-model:open="showSuspendDialog">
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Suspend User</DialogTitle>
					<DialogDescription>
						Are you sure you want to suspend this user? They will not be able to create
						listings or perform any transactions.
					</DialogDescription>
				</DialogHeader>
				<DialogFooter>
					<Button variant="outline" @click="showSuspendDialog = false">Cancel</Button>
					<Button variant="destructive" @click="handleSuspend">Suspend</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>

		<!-- Activate Dialog -->
		<Dialog v-model:open="showActivateDialog">
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Activate User</DialogTitle>
					<DialogDescription>
						Are you sure you want to activate this user? They will be able to resume
						normal account activities.
					</DialogDescription>
				</DialogHeader>
				<DialogFooter>
					<Button variant="outline" @click="showActivateDialog = false">Cancel</Button>
					<Button @click="handleActivate">Activate</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	</div>
</template>

<style scoped>
.card-shadow {
	box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}
</style>
