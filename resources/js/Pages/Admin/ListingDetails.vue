<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import { router } from "@inertiajs/vue3";
import {
	Card,
	CardContent,
	CardHeader,
	CardTitle,
	CardDescription,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import ListingImages from "@/Components/ListingImages.vue";
import { formatNumber } from "@/lib/formatters";
import Separator from "@/Components/ui/separator/Separator.vue";
import { ref } from "vue";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	listing: Object,
});

const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const showSuspendDialog = ref(false);
const showActivateDialog = ref(false);
const showTakedownDialog = ref(false);
const takedownReason = ref("");

const handleApprove = () => {
	router.patch(
		route("admin.listings.approve", props.listing.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => (showApproveDialog.value = false),
		}
	);
};

const handleReject = () => {
	router.patch(
		route("admin.listings.reject", props.listing.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => (showRejectDialog.value = false),
		}
	);
};

const handleSuspendUser = () => {
	router.patch(
		route("admin.users.suspend", props.listing.user.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => (showSuspendDialog.value = false),
		}
	);
};

const handleActivateUser = () => {
	router.patch(
		route("admin.users.activate", props.listing.user.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => (showActivateDialog.value = false),
		}
	);
};

const handleTakedown = () => {
	router.patch(
		route("admin.listings.takedown", props.listing.id),
		{ reason: takedownReason.value },
		{
			preserveScroll: true,
			onSuccess: () => {
				showTakedownDialog.value = false;
				takedownReason.value = "";
			},
		}
	);
};

const getStatusBadge = () => {
	return props.listing.approved
		? { variant: "success", label: "Approved" }
		: { variant: "warning", label: "Pending" };
};
</script>

<template>
	<Head :title="`| Admin - Listing Details: ${listing.title}`" />

	<div class="space-y-6">
		<div class="flex items-center justify-between">
			<h2 class="text-2xl font-semibold tracking-tight">{{ listing.title }}</h2>
			<Badge :variant="getStatusBadge().variant">
				{{ getStatusBadge().label }}
			</Badge>
		</div>

		<div class="lg:grid-cols-2 grid items-start grid-cols-1 gap-10">
			<!-- First Column (Details) -->
			<div class="grid gap-6">
				<!-- Images -->
				<div class="mb-10">
					<ListingImages
						:images="
							listing.images.length
								? listing.images
								: ['/storage/images/listing/default.png']
						"
					/>
				</div>

				<!-- Details -->
				<div class="space-y-1">
					<h2 class="text-lg font-semibold tracking-tight">Details</h2>
					<div class="text-muted-foreground">{{ listing.desc }}</div>
				</div>

				<Separator class="my-4" />

				<!-- Pricing Info -->
				<div class="space-y-1">
					<h2 class="text-lg font-semibold tracking-tight">Pricing Information</h2>
					<div class="space-y-2">
						<p>
							Item Value:
							<span class="text-muted-foreground">{{ formatNumber(listing.value) }}</span>
						</p>
						<p>
							Daily Rate:
							<span class="text-muted-foreground">{{ formatNumber(listing.price) }}</span>
						</p>
						<p>
							Category:
							<span class="text-muted-foreground">{{ listing.category.name }}</span>
						</p>
					</div>
				</div>

				<Separator class="my-4" />

				<!-- Location -->
				<div class="space-y-1">
					<h2 class="text-lg font-semibold tracking-tight">Location</h2>
					<div class="text-muted-foreground space-y-1">
						<p class="font-medium">{{ listing.location.name }}</p>
						<p>{{ listing.location.address }}</p>
						<p>
							{{ listing.location.city }}, {{ listing.location.province }}
							{{ listing.location.postal_code }}
						</p>
					</div>
				</div>

				<Separator class="my-4 lg:hidden" />
			</div>

			<!-- Second Column (Owner & Status) -->
			<div class="space-y-6">
				<!-- Owner Information -->
				<Card>
					<CardHeader>
						<CardTitle>Owner Information</CardTitle>
						<CardDescription>Details about the listing owner</CardDescription>
					</CardHeader>
					<CardContent class="space-y-4">
						<div class="space-y-2">
							<p>
								Name:
								<Link
									:href="route('admin.users.show', listing.user.id)"
									class="text-primary hover:underline"
									>{{ listing.user.name }}</Link
								>
							</p>
							<p>
								Email: <span class="text-muted-foreground">{{ listing.user.email }}</span>
							</p>
							<div class="flex items-center gap-2">
								<span>Status:</span>
								<Badge
									:variant="listing.user.status === 'active' ? 'success' : 'destructive'"
								>
									{{ listing.user.status }}
								</Badge>
							</div>
						</div>

						<!-- User Management Controls -->
						<div class="flex gap-2 pt-2">
							<Button
								v-if="listing.user.status === 'active'"
								variant="destructive"
								size="sm"
								@click="showSuspendDialog = true"
							>
								Suspend User
							</Button>
							<Button
								v-if="listing.user.status === 'suspended'"
								variant="default"
								size="sm"
								@click="showActivateDialog = true"
							>
								Activate User
							</Button>
							<Button
								variant="outline"
								size="sm"
								@click="router.get(route('admin.users.show', listing.user.id))"
							>
								View User Details
							</Button>
						</div>
					</CardContent>
				</Card>

				<!-- Listing Status -->
				<Card>
					<CardHeader>
						<CardTitle>Listing Status</CardTitle>
						<CardDescription>Current state of the listing</CardDescription>
					</CardHeader>
					<CardContent class="space-y-4">
						<div class="space-y-2">
							<p>
								Created:
								<span class="text-muted-foreground">
									{{ new Date(listing.created_at).toLocaleDateString() }}
								</span>
							</p>
							<p>
								Availability:
								<Badge :variant="listing.is_available ? 'success' : 'destructive'">
									{{ listing.is_available ? "Available" : "Not Available" }}
								</Badge>
							</p>
						</div>

						<!-- Listing Controls -->
						<div class="flex gap-2 pt-2" v-if="listing.status !== 'approved'">
							<Button variant="default" size="sm" @click="showApproveDialog = true"
								>Approve</Button
							>
							<Button variant="destructive" size="sm" @click="showRejectDialog = true"
								>Reject</Button
							>
						</div>
						<Button
							v-if="listing.status === 'approved'"
							variant="destructive"
							size="sm"
							@click="showTakedownDialog = true"
						>
							Take Down Listing
						</Button>
					</CardContent>
				</Card>
			</div>
		</div>
	</div>

	<ConfirmDialog
		:show="showApproveDialog"
		title="Approve Listing"
		description="Are you sure you want to approve this listing? It will become visible to all users."
		confirmLabel="Approve"
		confirmVariant="default"
		@update:show="showApproveDialog = $event"
		@confirm="handleApprove"
		@cancel="showApproveDialog = false"
	/>
	<ConfirmDialog
		:show="showRejectDialog"
		title="Reject Listing"
		description="Are you sure you want to reject this listing? This action cannot be undone."
		confirmLabel="Reject"
		confirmVariant="destructive"
		@update:show="showRejectDialog = $event"
		@confirm="handleReject"
		@cancel="showRejectDialog = false"
	/>
	<ConfirmDialog
		:show="showSuspendDialog"
		title="Suspend User Account"
		description="Are you sure you want to suspend {{ listing.user.name }}? This will also mark all their listings as unavailable."
		confirmLabel="Suspend User"
		confirmVariant="destructive"
		@update:show="showSuspendDialog = $event"
		@confirm="handleSuspendUser"
		@cancel="showSuspendDialog = false"
	/>
	<ConfirmDialog
		:show="showActivateDialog"
		title="Activate User Account"
		description="Are you sure you want to activate {{ listing.user.name }}'s account? This will allow them to list items and interact with the platform."
		confirmLabel="Activate User"
		confirmVariant="default"
		@update:show="showActivateDialog = $event"
		@confirm="handleActivateUser"
		@cancel="showActivateDialog = false"
	/>
	<ConfirmDialog
		:show="showTakedownDialog"
		title="Take Down Listing"
		description="This will immediately remove the listing from public view and notify the owner. Please provide a reason."
		confirmLabel="Take Down"
		confirmVariant="destructive"
		showTextarea
		:textareaValue="takedownReason"
		textareaPlaceholder="Enter reason for taking down this listing..."
		@update:show="showTakedownDialog = $event"
		@update:textareaValue="takedownReason = $event"
		@confirm="handleTakedown"
		@cancel="showTakedownDialog = false"
	/>
</template>
