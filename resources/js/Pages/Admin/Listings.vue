<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import AdminListingCard from "@/Components/AdminListingCard.vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	listings: Object,
});

const handleApprove = (listing) => {
	router.patch(
		route("admin.listings.approve", listing.id),
		{},
		{
			preserveScroll: true,
		}
	);
};

const handleReject = (listing) => {
	router.patch(
		route("admin.listings.reject", listing.id),
		{},
		{
			preserveScroll: true,
		}
	);
};
</script>

<template>
	<Head title="| Admin - Listings" />

	<div class="space-y-6">
		<div class="flex items-center justify-between">
			<h2 class="text-2xl font-semibold tracking-tight">Manage Listings</h2>
		</div>

		<div v-if="listings.data.length" class="space-y-4">
			<AdminListingCard
				v-for="listing in listings.data"
				:key="listing.id"
				:listing="listing"
				@approve="handleApprove"
				@reject="handleReject"
			/>

			<PaginationLinks :paginator="listings" />
		</div>
		<div v-else class="text-muted-foreground py-10 text-center">No listings found</div>
	</div>
</template>
