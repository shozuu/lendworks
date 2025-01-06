<script setup>
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { formatNumber } from "@/lib/formatters";
import { Tags, MapPin, PhilippinePeso } from "lucide-vue-next";
import { ref } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from "@/components/ui/dialog";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";

const props = defineProps({
	listing: {
		type: Object,
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
	<Card class="overflow-hidden">
		<div class="flex items-center gap-4 p-4">
			<!-- Thumbnail -->
			<div class="shrink-0 h-24 w-24 sm:h-32 sm:w-32 overflow-hidden rounded-md">
				<Link :href="route('listing.show', listing.id)">
					<img
						:src="
							listing.images[0]
								? `/storage/${listing.images[0].image_path}`
								: '/placeholder.jpg'
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
							:href="route('listing.show', listing.id)"
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
					</div>
					<Badge :variant="getStatusBadge(listing).variant">
						{{ getStatusBadge(listing).label }}
					</Badge>
				</div>

				<!-- Actions -->
				<div class="flex gap-2 pt-2 justify-end">
					<Link :href="route('listing.edit', listing.id)">
						<Button variant="outline" size="sm">Edit</Button>
					</Link>

					<Dialog v-model:open="showDeleteDialog">
						<DialogTrigger asChild>
							<Button variant="destructive" size="sm">Delete</Button>
						</DialogTrigger>
						<DialogContent>
							<DialogHeader>
								<DialogTitle>Delete Listing</DialogTitle>
								<DialogDescription>
									Are you sure you want to delete this listing? This action cannot be
									undone.
								</DialogDescription>
							</DialogHeader>
							<DialogFooter>
								<Button variant="outline" @click="showDeleteDialog = false">
									Cancel
								</Button>
								<Button
									variant="destructive"
									:disabled="deleteForm.processing"
									@click="handleDelete"
								>
									{{ deleteForm.processing ? "Deleting..." : "Delete" }}
								</Button>
							</DialogFooter>
						</DialogContent>
					</Dialog>

					<ConfirmDialog
						:show="showDeleteDialog"
						title="Delete Listing"
						description="Are you sure you want to delete this listing? This action cannot be undone."
						confirmLabel="Delete"
						confirmVariant="destructive"
						@update:show="showDeleteDialog = $event"
						@confirm="handleDelete"
						@cancel="showDeleteDialog = false"
					/>

					<Button
						v-if="listing.status === 'approved'"
						variant="outline"
						size="sm"
						@click="$emit('toggleAvailability', listing)"
					>
						{{ listing.is_available ? "Mark Unavailable" : "Mark Available" }}
					</Button>
				</div>
			</div>
		</div>
	</Card>
</template>
