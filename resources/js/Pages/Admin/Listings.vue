<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import AdminListingCard from "@/Components/AdminListingCard.vue";
import { Button } from "@/components/ui/button";
import { router } from "@inertiajs/vue3";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	listings: Object,
	rejectionReasons: {
		type: Array,
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
</script>

<template>
	<Head title="| Admin - Listings" />

	<div class="space-y-6">
		<div class="flex items-center justify-between">
			<h2 class="text-2xl font-semibold tracking-tight">Listings</h2>
		</div>

		<div class="grid gap-4">
			<AdminListingCard
				v-for="listing in listings.data"
				:key="listing.id"
				:listing="listing"
				:rejection-reasons="rejectionReasons"
				@update-status="handleUpdateStatus"
			/>
		</div>

		<!-- Pagination controls if needed -->
	</div>
</template>
