<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import AdminListingCard from "@/Components/AdminListingCard.vue";
import { Badge } from "@/components/ui/badge";
import { router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { Input } from "@/components/ui/input";
import {
	Select,
	SelectTrigger,
	SelectValue,
	SelectContent,
	SelectItem,
	SelectLabel,
} from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { debounce } from "lodash";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	listings: Object,
	rejectionReasons: {
		type: Array,
		required: true,
	},
	filters: {
		type: Object,
		default: () => ({
			search: "",
			status: "all",
			sortBy: "latest",
		}),
	},
	listingCounts: {
		type: Object,
		required: true,
	},
});

const handleUpdateStatus = async ({ listing, status, reason }) => {
	if (status === "approved") {
		await router.patch(route("admin.listings.approve", listing.id));
	} else if (status === "rejected" && reason) {
		await router.patch(
			route("admin.listings.reject", listing.id),
			{
				rejection_reason: reason,
			},
			{
				preserveScroll: true,
			}
		);
	}
};

const search = ref(props.filters?.search ?? "");
const statusFilter = ref(props.filters?.status ?? "all");
const sortBy = ref(props.filters?.sortBy ?? "latest");

// Update search with debounce
const updateSearch = debounce((value) => {
	router.get(
		route("admin.listings"),
		{
			...props.filters,
			search: value,
			page: 1, // Reset to first page on new search
		},
		{
			preserveState: true,
			preserveScroll: false,
			replace: true,
		}
	);
}, 300);

// Update filters
const updateFilters = (newFilters) => {
	router.get(
		route("admin.listings"),
		{
			...props.filters,
			...newFilters,
			page: 1, // Reset to first page on filter change
		},
		{
			preserveState: true,
			preserveScroll: false,
		}
	);
};

// Watch for changes
watch(search, (newVal) => {
	updateSearch(newVal);
});

watch(statusFilter, (newVal) => {
	updateFilters({ status: newVal });
});

watch(sortBy, (newVal) => {
	updateFilters({ sortBy: newVal });
});
</script>

<template>
	<Head title="| Admin - Listings" />

	<div class="space-y-6">
		<!-- Header with stats -->
		<div class="flex flex-col gap-4">
			<div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:justify-between">
				<h2 class="text-2xl font-semibold tracking-tight">Manage Listings</h2>
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
			<div class="flex flex-col sm:flex-row gap-3">
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
		<div v-if="listings.data.length" class="grid gap-4">
			<AdminListingCard
				v-for="listing in listings.data"
				:key="listing.id"
				:listing="listing"
				:rejection-reasons="rejectionReasons"
				@update-status="handleUpdateStatus"
			/>
		</div>
		<div
			v-else-if="search || statusFilter !== 'all'"
			class="text-muted-foreground py-8 text-center rounded-lg border bg-card"
		>
			No listings match your filters.
		</div>
		<div v-else class="text-muted-foreground py-8 text-center rounded-lg border bg-card">
			No listings found.
		</div>

		<!-- Pagination -->
		<PaginationLinks v-if="listings.data.length" :paginator="listings" />
	</div>
</template>
