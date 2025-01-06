<script setup>
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { formatNumber } from "@/lib/formatters";
import { Tags, MapPin, PhilippinePeso } from "lucide-vue-next";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";

const props = defineProps({
	listing: {
		type: Object,
		required: true,
	},
});

const emit = defineEmits(["approve", "reject"]);

const showApproveDialog = ref(false);
const showRejectDialog = ref(false);

const getStatusBadge = (listing) => {
	switch (listing.status) {
		case "approved":
			return {
				label: listing.is_available ? "Available" : "Not Available",
				variant: listing.is_available ? "success" : "destructive",
			};
		case "rejected":
			return {
				label: "Rejected",
				variant: "destructive",
			};
		case "pending":
		default:
			return {
				label: "Pending Approval",
				variant: "warning",
			};
	}
};
</script>

<template>
	<Card class="overflow-hidden">
		<div class="flex items-center gap-4 p-4">
			<!-- Thumbnail -->
			<div class="shrink-0 h-24 w-24 sm:h-32 sm:w-32 overflow-hidden rounded-md">
				<Link :href="route('admin.listings.show', listing.id)">
					<img
						:src="
							listing.images[0]
								? `/storage/${listing.images[0].image_path}`
								: '/storage/images/listing/default.png'
						"
						:alt="listing.title"
						class="h-full w-full object-cover"
					/>
				</Link>
			</div>

			<!-- Content -->
			<div class="flex flex-1 flex-col gap-2">
				<div class="flex items-start justify-between gap-4">
					<div class="space-y-1">
						<Link
							:href="route('admin.listings.show', listing.id)"
							class="font-semibold hover:underline line-clamp-1"
						>
							{{ listing.title }}
						</Link>
						<div class="flex flex-col gap-1 text-sm text-muted-foreground">
							<div class="flex items-center gap-1">
								<Tags class="w-4 h-4" />
								{{ listing.category?.name }}
							</div>
							<div class="flex items-center gap-1">
								<PhilippinePeso class="w-4 h-4" />
								{{ formatNumber(listing.price) }}/day
							</div>
							<div
								class="flex items-center gap-1 truncate max-w-[200px] sm:max-w-[300px]"
							>
								<MapPin class="w-4 h-4 shrink-0" />
								<span class="truncate">
									{{ listing.location?.address ?? "No address specified" }},
									{{ listing.location?.city ?? "No city specified" }}
								</span>
							</div>
						</div>
						<div class="flex items-center gap-2 pt-1">
							<span class="text-sm">Owner:</span>
							<Link
								:href="route('admin.users.show', listing.user.id)"
								class="text-sm text-primary hover:underline"
							>
								{{ listing.user.name }}
							</Link>
						</div>
					</div>
					<Badge :variant="getStatusBadge(listing).variant">
						{{ getStatusBadge(listing).label }}
					</Badge>
				</div>

				<!-- Actions -->
				<div class="flex gap-2 pt-2 justify-end">
					<Button
						v-if="listing.status === 'pending'"
						variant="default"
						size="sm"
						@click="showApproveDialog = true"
					>
						Approve
					</Button>
					<Button
						v-if="listing.status === 'pending'"
						variant="destructive"
						size="sm"
						@click="showRejectDialog = true"
					>
						Reject
					</Button>
					<Button
						variant="outline"
						size="sm"
						@click="router.visit(route('admin.listings.show', listing.id))"
					>
						View Details
					</Button>
				</div>
			</div>
		</div>

		<!-- Dialogs -->
		<ConfirmDialog
			:show="showApproveDialog"
			title="Approve Listing"
			description="Are you sure you want to approve this listing? It will become visible to all users."
			confirmLabel="Approve"
			confirmVariant="default"
			@update:show="showApproveDialog = $event"
			@confirm="$emit('approve', listing)"
			@cancel="showApproveDialog = false"
		/>

		<ConfirmDialog
			:show="showRejectDialog"
			title="Reject Listing"
			description="Are you sure you want to reject this listing? This action cannot be undone."
			confirmLabel="Reject"
			confirmVariant="destructive"
			@update:show="showRejectDialog = $event"
			@confirm="$emit('reject', listing)"
			@cancel="showRejectDialog = false"
		/>
	</Card>
</template>
