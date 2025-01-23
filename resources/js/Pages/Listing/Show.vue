<script setup>
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import ListingImages from "@/Components/ListingImages.vue";
import { Card, CardContent } from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import Separator from "@/Components/ui/separator/Separator.vue";
import Button from "@/Components/ui/button/Button.vue";
import RentalForm from "@/Components/RentalForm.vue";
import { Link } from "@inertiajs/vue3";
import { useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";
import ItemCard from "@/Components/ItemCard.vue";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { AlertTriangle, XCircle } from "lucide-vue-next";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import { formatDate, timeAgo } from "@/lib/formatters";

const props = defineProps({
	listing: Object,
	relatedListings: Object,
	showPendingMessage: Boolean,
	justUpdated: Boolean,
});

const showDeleteDialog = ref(false);
const deleteForm = useForm({});

const handleDelete = () => {
	deleteForm.delete(route("listing.destroy", props.listing.id), {
		onSuccess: () => {
			showDeleteDialog.value = false;
			// prevent back navigation to deleted listing
			router.visit(route("my-listings"), {
				preserveState: false,
				preserveScroll: false,
				replace: true,
				onFinish: () => {
					// clear the browser's entry for the deleted page
					window.history.replaceState(null, "", route("my-listings"));
				},
			});
		},
	});
};
</script>

<template>
	<Head :title="`| Rent ${listing.title}`" />

	<Breadcrumbs
		:currentPage="listing.title"
		:category="listing.category.name"
		class="lg:flex hidden mb-10"
	/>

	<!-- Pending Status Alert -->
	<Alert v-if="listing.status === 'pending'" variant="warning" class="mb-6">
		<AlertTriangle class="w-4 h-4" />
		<AlertDescription v-if="justUpdated">
			Listing has been successfully updated. It will be visible to other users once
			approved.
		</AlertDescription>
		<AlertDescription v-else>
			This listing is pending approval from moderators. It will be visible to other users
			once approved.
		</AlertDescription>
	</Alert>

	<!-- takedown/rejection notice -->
	<div
		v-if="
			(listing.status === 'rejected' && listing.latest_rejection) ||
			(listing.status === 'taken_down' && listing.latest_takedown)
		"
		class="bg-destructive/10 mb-6 overflow-hidden rounded-lg"
	>
		<!-- header -->
		<div class="bg-destructive/15 px-4 sm:px-6 py-4 border-b border-destructive/10">
			<div class="flex items-center gap-2">
				<XCircle class="w-5 h-5 shrink-0 text-destructive" />
				<h3 class="text-destructive font-semibold">
					This Listing Has Been
					{{ listing.status === "rejected" ? "Rejected" : "Taken Down" }}
				</h3>
			</div>
		</div>

		<!-- content -->
		<div class="px-4 sm:px-6 py-4 space-y-4">
			<!-- main reason -->
			<div class="space-y-2">
				<h4 class="font-medium">
					Reason for {{ listing.status === "rejected" ? "Rejection" : "Take Down" }}:
				</h4>
				<div class="bg-background p-4 border rounded-md">
					{{
						listing.status === "rejected"
							? listing.latest_rejection.rejection_reason.description
							: listing.latest_takedown.takedown_reason.description
					}}
				</div>
			</div>

			<!-- Required Actions (Only for Rejected Listings) -->
			<div v-if="listing.status === 'rejected'" class="space-y-2">
				<h4 class="font-medium">How to Fix This:</h4>
				<div class="bg-background p-4 space-y-2 border rounded-md">
					<p>{{ listing.latest_rejection.rejection_reason.action_needed }}</p>
				</div>
			</div>

			<!-- Admin Feedback -->
			<div
				v-if="
					(listing.status === 'rejected' && listing.latest_rejection.custom_feedback) ||
					(listing.status === 'taken_down' && listing.latest_takedown.custom_feedback)
				"
				class="space-y-2"
			>
				<h4 class="font-medium">Additional Feedback:</h4>
				<div class="bg-background p-4 space-y-2 border rounded-md">
					<p class="text-muted-foreground">
						{{
							listing.status === "rejected"
								? listing.latest_rejection.custom_feedback
								: listing.latest_takedown.custom_feedback
						}}
					</p>
				</div>
			</div>

			<p class="text-muted-foreground text-xs">
				{{ listing.status === "rejected" ? "Rejected" : "Taken down" }}
				{{
					timeAgo(
						listing.status === "rejected"
							? listing.latest_rejection.created_at
							: listing.latest_takedown.created_at
					)
				}}
			</p>
		</div>
	</div>

	<div
		v-if="
			listing.status === 'taken_down' && listing.user.id === $page.props.auth.user?.id
		"
		class="bg-muted p-4 rounded-lg mb-6"
	>
		<p class="text-sm text-muted-foreground">
			This listing has been taken down and cannot be edited. You'll need to create a new
			listing that follows our platform guidelines.
		</p>
	</div>

	<div
		v-else-if="
			listing.status === 'rejected' && listing.user.id === $page.props.auth.user?.id
		"
		class="bg-muted p-4 rounded-lg mb-6"
	>
		<p class="text-sm text-muted-foreground">
			This listing has been rejected. You can edit it to address the issues and resubmit
			for approval.
		</p>
	</div>

	<h2 class="mb-6 text-2xl font-semibold tracking-tight">
		{{ listing.title }}
	</h2>

	<div class="lg:grid-cols-2 grid grid-cols-1 gap-4 lg:gap-10">
		<!-- first column -->

		<div class="space-y-6">
			<!-- images -->
			<div class="w-full">
				<ListingImages
					:images="
						listing.images.length
							? listing.images
							: ['/storage/images/listing/default.png']
					"
					class="aspect-[4/3] sm:aspect-[16/9] lg:aspect-[4/3]"
				/>
			</div>

			<!-- desc -->
			<p class="text-sm sm:text-base text-muted-foreground">{{ listing.desc }}</p>

			<Separator class="my-4" />

			<!-- cancellation terms -->
			<div class="space-y-1">
				<h2 class="text-lg font-semibold tracking-tight">Cancellation Terms</h2>

				<div class="space-y-2">
					<div class="text-muted-foreground">
						<span class="font-semibold">Full Refund:</span> If the renter cancels well in
						advance (e.g., 48-72 hours before the rental period begins).
					</div>
					<div class="text-muted-foreground">
						<span class="font-semibold">Partial refund (50%):</span> If the renter cancels
						closer to the rental date (e.g., 24-48 hours before).
					</div>
					<div class="text-muted-foreground">
						<span class="font-semibold">No refund:</span> If the renter cancels last
						minute (e.g., less than 24 hours before the rental period starts) or after the
						rental period has begun.
					</div>
				</div>
			</div>

			<Separator class="my-4" />

			<!-- location -->
			<div class="space-y-1">
				<h2 class="text-lg font-semibold tracking-tight">Listing Location</h2>

				<div class="text-muted-foreground space-y-1">
					<p class="font-medium">{{ listing.location.name }}</p>
					<p>{{ listing.location.address }}</p>
					<p>
						{{ listing.location.city }}, {{ listing.location.province }}
						{{ listing.location.postal_code }}
					</p>
				</div>
			</div>

			<Separator class="my-4" />

			<!-- user info -->
			<Card>
				<CardContent
					class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-4 p-4 text-sm"
				>
					<div class="flex items-center gap-4">
						<Avatar>
							<AvatarImage src="https://picsum.photos/200" />
							<AvatarFallback>AT</AvatarFallback>
						</Avatar>

						<div>
							<h4 class="font-semibold">Listed By {{ listing.user.name }}</h4>

							<div class="text-muted-foreground text-xs mt-1">
								<p>Joined {{ formatDate(listing.user.created_at) }}</p>
								<p>Listed {{ timeAgo(listing.created_at) }}</p>
							</div>
						</div>
					</div>

					<div
						v-if="listing.user.id === $page.props.auth.user?.id"
						class="sm:w-auto grid w-full grid-cols-2 gap-2"
					>
						<Button
							variant="outline"
							class="w-full disabled:cursor-not-allowed disabled:pointer-events-auto"
							:disabled="listing.status === 'taken_down'"
							@click.stop="router.visit(route('listing.edit', listing.id))"
						>
							Edit
						</Button>

						<Button variant="destructive" class="w-full" @click="showDeleteDialog = true">
							Delete
						</Button>
					</div>

					<Link v-else href="" class="sm:w-auto w-full">
						<Button class="sm:w-auto w-full">View Profile</Button>
					</Link>
				</CardContent>
			</Card>
		</div>

		<!-- second column -->
		<div class="lg:sticky lg:top-4 space-y-6">
			<RentalForm
				:listing="listing"
				:is-owner="listing.user.id === $page.props.auth.user?.id"
				class="w-full lg:min-w-[400px]"
			/>
		</div>
	</div>

	<Separator class="my-10" />

	<!-- related listings -->
	<div class="space-y-6">
		<div class="space-y-1">
			<h2 class="text-lg font-semibold tracking-tight">Similar Tools</h2>
			<p class="text-muted-foreground">Other tools you might be interested in</p>
		</div>

		<div
			v-if="relatedListings?.length"
			class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
		>
			<div v-for="listing in relatedListings" :key="listing.id">
				<ItemCard :listing="listing" />
			</div>
		</div>
		<div v-else class="text-muted-foreground">No similar tools found.</div>
	</div>

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
</template>
