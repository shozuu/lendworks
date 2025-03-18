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
import { Button } from "@/components/ui/button";
import { Download } from "lucide-vue-next";
import { formatDate } from "@/lib/formatters";  // Add this import

defineOptions({ layout: AdminLayout });

const props = defineProps({
	listings: Object,
	rejectionReasons: {
		type: Array,
		required: true,
	},
	categories: {
		type: Array,
		required: true,
	},
	filters: {
		type: Object,
		default: () => ({
			search: "",
			status: "all",
			sortBy: "latest",
			category: "all", // Added category filter default
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
const category = ref(props.filters?.category ?? "all");

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

watch(category, (newVal) => {
	updateFilters({ category: newVal });
});

const exportToCSV = async () => {
    try {
        // Fetch all listings data from the export endpoint
        const response = await fetch(route('admin.listings.export'), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) throw new Error('Failed to fetch data');
        const allListings = await response.json();

        // Define headers
        const headers = [
            'Title',
            'Category',
            'Price',
            'Status',
            'Lender',
            'Location',
            'Created At',
            'Available',
            'Description'
        ].join(',');

        // Transform all listings data into CSV rows
        const rows = allListings.map(listing => [
            `"${listing.title.replace(/"/g, '""')}"`,
            `"${listing.category || 'Uncategorized'}"`,
            listing.price,
            listing.status,
            `"${listing.lender}"`,
            `"${listing.location || 'Unknown'}"`,
            listing.created_at,
            listing.is_available ? 'Yes' : 'No',
            `"${(listing.description || '').replace(/"/g, '""')}"`
        ].join(','));

        // Create and download CSV
        const csv = [headers, ...rows].join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `listings-${formatDate(new Date())}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

    } catch (error) {
        console.error('Export failed:', error);
    }
};
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
					<!-- Category Filter - New -->
					<Select v-model="category">
						<SelectTrigger class="w-[200px]">
							<SelectValue placeholder="Filter by category" />
						</SelectTrigger>
						<SelectContent>
							<SelectLabel class="px-2 py-1.5">Filter Category</SelectLabel>
							<Separator class="my-2" />
							<SelectItem value="all" class="flex items-center justify-between">
								<span>All Categories</span>
								<span class="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
									{{ listingCounts.total }}
								</span>
							</SelectItem>
							<SelectItem 
								v-for="cat in categories" 
								:key="cat.id" 
								:value="cat.id.toString()"
								class="flex items-center justify-between"
							>
								<span>{{ cat.name }}</span>
								<span class="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
									{{ cat.count }}
								</span>
							</SelectItem>
						</SelectContent>
					</Select>

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

					<Button @click="exportToCSV" variant="outline" class="gap-2">
						<Download class="h-4 w-4" />
						Export CSV
					</Button>
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

<style scoped>
/* Add these styles for consistent alignment */
:deep(.select-content) {
    min-width: 200px;
}

:deep(.select-item) {
    padding-right: 1rem;
}
</style>
