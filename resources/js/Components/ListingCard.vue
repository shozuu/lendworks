<script setup>
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { formatNumber, timeAgo } from "@/lib/formatters";
import { Tags, MapPin, PhilippinePeso, XCircle, Clock } from "lucide-vue-next";
import { ref } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";

const props = defineProps({
	listing: {
		type: Object,
		required: true,
	},
	rejectionReasons: {
		type: Array,
		required: true,
	},
});

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

const emit = defineEmits(["toggleAvailability"]);

const showDeleteDialog = ref(false);
const deleteForm = useForm({});

const handleDelete = () => {
	deleteForm.delete(route("listing.destroy", props.listing.id), {
		onSuccess: () => {
			showDeleteDialog.value = false;
			// Redirect and prevent back navigation to deleted listing
			router.visit(route("my-listings"), {
				preserveState: false,
				preserveScroll: true,
				replace: true,
				onFinish: () => {
					window.history.replaceState(null, "", route("my-listings"));
				},
			});
		},
	});
};
</script>

<template>
	<Card class="h-full">
		<!-- rejection reason -->
		<div
			v-if="listing.status === 'rejected' && listing.latest_rejection"
			class="bg-destructive/10 p-3 text-sm"
		>
			<div
				class="flex flex-col items-center gap-2 text-destructive sm:flex-row sm:items-start"
			>
				<div class="flex items-center gap-2">
					<XCircle class="w-4 h-4 shrink-0" />
					<p class="font-medium">Rejection Reason:</p>
				</div>
				{{ listing.latest_rejection.rejection_reason.label }}
			</div>
		</div>

		<div class="flex flex-col sm:flex-row gap-4 p-4">
			<!-- thumbnail -->
			<div class="sm:w-32 sm:h-32 w-24 h-24 overflow-hidden rounded-md shrink-0">
				<Link :href="route('listing.show', listing.id)" class="h-full">
					<img
						:src="
							listing.images[0]
								? `/storage/${listing.images[0].image_path}`
								: '/placeholder.jpg'
						"
						:alt="listing.title"
						class="object-cover w-full h-full"
					/>
				</Link>
			</div>

			<div class="flex-1 min-w-0 flex flex-col gap-3">
				<!-- title and badge -->
				<div class="flex items-start flex-col sm:flex-row justify-between gap-1">
					<Link
						:href="route('listing.show', listing.id)"
						class="hover:underline font-semibold text-sm sm:text-base line-clamp-1"
					>
						{{ listing.title }}
					</Link>
					<Badge
						:variant="getStatusBadge(listing).variant"
						class="whitespace-nowrap shrink-0"
					>
						{{ getStatusBadge(listing).label }}
					</Badge>
				</div>

				<!-- details -->
				<div class="text-muted-foreground text-xs sm:text-sm space-y-1.5 flex-1">
					<div class="flex items-center gap-1">
						<Tags class="w-4 h-4 shrink-0" />
						<span class="truncate">{{ listing.category?.name }}</span>
					</div>
					<div class="flex items-center gap-1">
						<PhilippinePeso class="w-4 h-4 shrink-0" />
						<span class="whitespace-nowrap">{{ formatNumber(listing.price) }}/day</span>
					</div>
					<div class="flex items-center gap-1">
						<MapPin class="w-4 h-4 shrink-0" />
						<span class="line-clamp-1">
							{{ listing.location?.address ?? "No location specified" }}
						</span>
					</div>
					<!-- date -->
					<div class="flex items-center gap-1">
						<Clock class="w-4 h-4 shrink-0" />
						<span>{{ timeAgo(listing.created_at) }}</span>
					</div>
				</div>

				<!-- actions -->
				<div class="flex flex-wrap gap-2 sm:justify-end">
					<Link :href="route('listing.edit', listing.id)">
						<Button variant="outline" size="sm">Edit</Button>
					</Link>

					<Button
						v-if="listing.status === 'approved'"
						variant="outline"
						size="sm"
						@click="$emit('toggleAvailability', listing)"
					>
						{{ listing.is_available ? "Mark Unavailable" : "Mark Available" }}
					</Button>

					<Button variant="destructive" size="sm" @click="showDeleteDialog = true">
						Delete
					</Button>
				</div>
			</div>
		</div>

		<ConfirmDialog
			:show="showDeleteDialog"
			title="Delete Listing"
			description="Are you sure you want to delete this listing? This action cannot be undone."
			confirmLabel="Delete"
			confirmVariant="destructive"
			:processing="deleteForm.processing"
			@update:show="showDeleteDialog = $event"
			@confirm="handleDelete"
			@cancel="showDeleteDialog = false"
		/>
	</Card>
</template>
