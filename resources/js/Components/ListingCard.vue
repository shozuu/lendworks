<script setup>
import BaseListingCard from "./BaseListingCard.vue";
import { Button } from "@/components/ui/button";
import { XCircle } from "lucide-vue-next";
import { ref } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";

const props = defineProps({
	listing: {
		type: Object,
		required: true,
	},
});

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
	<BaseListingCard :listing="listing">
		<!-- Banner slot -->
		<template
			#banner
			v-if="
				(listing.status === 'rejected' && listing.latest_rejection) ||
				(listing.status === 'taken_down' && listing.latest_takedown)
			"
		>
			<div class="bg-destructive/10 p-3 text-sm">
				<div
					class="flex flex-col items-center gap-2 text-destructive sm:flex-row sm:items-start"
				>
					<div class="flex items-center gap-2">
						<XCircle class="shrink-0 w-4 h-4" />
						<p class="font-medium">
							{{ listing.status === "rejected" ? "Rejection" : "Takedown" }} Reason:
						</p>
					</div>
					{{
						listing.status === "rejected"
							? listing.latest_rejection.rejection_reason.label
							: listing.latest_takedown.takedown_reason.label
					}}
				</div>
			</div>
		</template>

		<!-- thumbnail, content (title and status), and details are in BaseListingCard  -->

		<!-- Actions slot -->
		<template #actions>
			<div class="sm:justify-end flex flex-wrap gap-2">
				<Button
					variant="outline"
					size="sm"
					:disabled="listing.status === 'taken_down'"
					@click.stop="router.visit(route('listing.edit', listing.id))"
					class="disabled:cursor-not-allowed disabled:pointer-events-auto"
				>
					Edit
				</Button>

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
		</template>
	</BaseListingCard>

	<!-- Delete Dialog -->
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
</template>
