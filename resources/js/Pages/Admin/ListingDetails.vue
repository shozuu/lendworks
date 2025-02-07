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
import { formatNumber, formatDateTime, timeAgo } from "@/lib/formatters";
import Separator from "@/Components/ui/separator/Separator.vue";
import { ref, computed } from "vue";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import { XCircle } from "lucide-vue-next";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { History, CalendarClock, AlertCircle } from "lucide-vue-next";
import ListingStatusBadge from "@/Components/ListingStatusBadge.vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	listing: Object,
	rejectionReasons: {
		// for populating select options
		type: Array,
		required: true,
	},
	takedownReasons: {
		// for populating select options
		type: Array,
		required: true,
	},
});

console.log(props.listing);

const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const showSuspendDialog = ref(false);
const showActivateDialog = ref(false);
const showTakedownDialog = ref(false);
const showRejectionHistory = ref(false);

const isApproving = ref(false);
const isRejecting = ref(false);
const isTakingDown = ref(false);
const isSuspending = ref(false);
const isActivating = ref(false);

const selectedReason = ref("");
const customFeedback = ref("");
const selectedTakedownReason = ref("");
const takedownFeedback = ref("");

const handleApprove = async () => {
	if (isApproving.value) return;
	isApproving.value = true;

	await router.patch(
		route("admin.listings.approve", props.listing.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => {
				showApproveDialog.value = false;
			},
			onFinish: () => {
				isApproving.value = false;
			},
		}
	);
};

const handleReject = async () => {
	if (isRejecting.value || !selectedReason.value) return;
	isRejecting.value = true;

	await router.patch(
		route("admin.listings.reject", props.listing.id),
		{ rejection_reason: selectedReason.value, feedback: customFeedback.value },
		{
			preserveScroll: true,
			onSuccess: () => {
				showRejectDialog.value = false;
				selectedReason.value = "";
				customFeedback.value = "";
			},
			onFinish: () => {
				isRejecting.value = false;
			},
		}
	);
};

const handleSuspendUser = async () => {
	if (isSuspending.value) return;
	isSuspending.value = true;

	await router.patch(
		route("admin.users.suspend", props.listing.user.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => {
				showSuspendDialog.value = false;
			},
			onFinish: () => {
				isSuspending.value = false;
			},
		}
	);
};

const handleActivateUser = async () => {
	if (isActivating.value) return;
	isActivating.value = true;

	await router.patch(
		route("admin.users.activate", props.listing.user.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => {
				showActivateDialog.value = false;
			},
			onFinish: () => {
				isActivating.value = false;
			},
		}
	);
};

const handleTakedown = async () => {
	if (isTakingDown.value || !selectedTakedownReason.value) return;
	isTakingDown.value = true;

	await router.patch(
		route("admin.listings.takedown", props.listing.id),
		{
			takedown_reason: selectedTakedownReason.value,
			feedback: takedownFeedback.value,
		},
		{
			preserveScroll: true,
			onSuccess: () => {
				showTakedownDialog.value = false;
				selectedTakedownReason.value = "";
				takedownFeedback.value = "";
			},
			onFinish: () => {
				isTakingDown.value = false;
			},
		}
	);
};

const isOtherReason = computed(() => {
	const selected = props.rejectionReasons.find((r) => r.value === selectedReason.value);
	return selected?.code === "other";
});

const isOtherTakedownReason = computed(() => {
	const selected = props.takedownReasons.find(
		(r) => r.value === selectedTakedownReason.value
	);
	return selected?.code === "other";
});

const handleCancelReject = () => {
	showRejectDialog.value = false;
	selectedReason.value = "";
	customFeedback.value = "";
};

const handleCancelTakedown = () => {
	showTakedownDialog.value = false;
	selectedTakedownReason.value = "";
	takedownFeedback.value = "";
};

const latestRejection = computed(() => {
	const rejection = props.listing.rejection_reasons?.[0];
	if (!rejection) return null;

	return {
		...rejection,
		admin_name: rejection.admin_name,
		custom_feedback: rejection.custom_feedback,
	};
});

const latestTakedown = computed(() => {
	const takedown = props.listing.takedown_reasons?.[0];
	if (!takedown) return null;

	return {
		...takedown,
		admin_name: takedown.admin_name,
		custom_feedback: takedown.custom_feedback,
	};
});

const sortedRejectionHistory = computed(() => {
	if (!props.listing.rejection_reasons?.length) return [];
	return props.listing.rejection_reasons.map((rejection) => ({
		...rejection,
		formattedDate: formatDateTime(rejection.rejected_at),
		adminName: rejection.admin_name,
		feedback: rejection.custom_feedback,
	}));
});

const hasRejectionHistory = computed(() => props.listing.rejection_reasons?.length > 0);
</script>

<template>
	<Head :title="`| Admin - Listing Details: ${listing.title}`" />

	<!-- Update the main container spacing -->
	<div class="sm:space-y-6 space-y-4">
		<!-- Make header more responsive -->
		<div
			class="sm:flex-row sm:items-center sm:justify-between flex flex-col items-start gap-2"
		>
			<h2 class="sm:text-2xl text-xl font-semibold tracking-tight">
				{{ listing.title }}
			</h2>

			<ListingStatusBadge :status="listing.status" :is-available="listing.is_available" />
		</div>

		<!-- grid layout -->
		<div class="lg:grid-cols-2 lg:gap-10 grid grid-cols-1 gap-4">
			<!-- First Column (Details) -->
			<div class="sm:gap-6 grid gap-4">
				<!-- Make images container responsive -->
				<div class="sm:mb-6 lg:mb-10 mb-4">
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
							Security Deposit:
							<span class="text-muted-foreground">{{
								formatNumber(listing.deposit_fee)
							}}</span>
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

				<Separator class="lg:hidden my-4" />
			</div>

			<!-- Second Column (Owner & Status) -->
			<div class="sm:space-y-6 space-y-4">
				<!-- Owner Information -->
				<Card>
					<CardHeader class="sm:p-6 px-4">
						<CardTitle>Owner Information</CardTitle>
						<CardDescription>Details about the listing owner</CardDescription>
					</CardHeader>
					<CardContent class="sm:px-6 sm:pb-6 px-4 pb-4 space-y-4">
						<div class="sm:space-y-3 space-y-2">
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
									{{ listing.user.status === "active" ? "Active" : "Suspended" }}
								</Badge>
							</div>
						</div>

						<!-- User Management Controls -->
						<div class="flex flex-wrap gap-2 pt-2">
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
						</div>
					</CardContent>
				</Card>

				<!-- Listing Status -->
				<Card>
					<CardHeader class="sm:p-6 p-4">
						<CardTitle>Listing Status</CardTitle>
						<CardDescription>Current state of the listing</CardDescription>
					</CardHeader>
					<CardContent class="sm:px-6 sm:pb-6 px-4 pb-4 space-y-4">
						<!-- takedown/rejection banner -->
						<div
							v-if="
								(listing.status === 'rejected' && latestRejection) ||
								(listing.status === 'taken_down' && latestTakedown)
							"
							class="bg-destructive/5 border-destructive/20 overflow-hidden rounded-lg"
						>
							<!-- Header -->
							<div
								class="bg-destructive/10 sm:px-4 sm:py-3 border-destructive/10 px-3 py-2 border-b"
							>
								<div
									class="sm:flex-row sm:items-center flex flex-col justify-between gap-2"
								>
									<div class="flex items-center gap-2">
										<XCircle class="text-destructive w-5 h-5" />
										<h3 class="text-destructive font-medium">
											{{
												listing.status === "rejected"
													? latestRejection.label
													: latestTakedown.label
											}}
										</h3>
									</div>
									<div class="text-muted-foreground flex items-center gap-2 text-sm">
										<CalendarClock class="w-4 h-4" />
										<time>{{
											timeAgo(
												listing.status === "rejected"
													? latestRejection.pivot.created_at
													: latestTakedown.pivot.created_at
											)
										}}</time>
									</div>
								</div>
							</div>

							<!-- Content -->
							<div class="sm:p-4 sm:space-y-4 p-3 space-y-3">
								<!-- Description -->
								<p class="text-muted-foreground text-sm leading-relaxed">
									{{
										listing.status === "rejected"
											? latestRejection.description
											: latestTakedown.description
									}}
								</p>

								<!-- Admin Feedback if exists -->
								<div class="bg-background border rounded p-3 space-y-1.5">
									<template
										v-if="
											listing.status === 'rejected'
												? latestRejection.custom_feedback
												: latestTakedown.custom_feedback
										"
									>
										<p
											class="text-muted-foreground/70 text-xs font-medium tracking-wider uppercase"
										>
											Admin Feedback
										</p>
										<p class="text-muted-foreground text-sm">
											{{
												listing.status === "rejected"
													? latestRejection.custom_feedback
													: latestTakedown.custom_feedback
											}}
										</p>
									</template>

									<!-- Always show admin info -->
									<p
										class="text-muted-foreground text-xs"
										:class="{
											'border-t pt-2 mt-2':
												listing.status === 'rejected'
													? latestRejection.custom_feedback
													: latestTakedown.custom_feedback,
										}"
									>
										{{ listing.status === "rejected" ? "Rejected" : "Taken down" }} by
										{{
											listing.status === "rejected"
												? latestRejection.admin_name
												: latestTakedown.admin_name
										}}
									</p>
								</div>
							</div>
						</div>

						<div class="space-y-2">
							<p>
								Created:
								<span class="text-muted-foreground">
									{{ formatDateTime(listing.created_at) }}
								</span>
							</p>
							<p>
								Last Updated:
								<span class="text-muted-foreground">
									{{ timeAgo(listing.updated_at) }}
								</span>
							</p>
							<p>
								Availability:
								<Badge :variant="listing.is_available ? 'success' : 'destructive'">
									{{ listing.is_available ? "Available" : "Not Available" }}
								</Badge>
							</p>
						</div>

						<!-- actions -->
						<div class="space-y-6">
							<div v-if="listing.status === 'pending'" class="flex flex-wrap gap-2">
								<Button variant="default" size="sm" @click="showApproveDialog = true">
									Approve
								</Button>
								<Button variant="destructive" size="sm" @click="showRejectDialog = true">
									Reject
								</Button>
							</div>

							<!-- takedown listing -->
							<Button
								v-if="listing.status === 'approved'"
								variant="destructive"
								size="sm"
								@click="showTakedownDialog = true"
							>
								Takedown Listing
							</Button>

							<!-- always show if there's history -->
							<Button
								v-if="hasRejectionHistory"
								variant="outline"
								size="sm"
								class="w-full"
								@click="showRejectionHistory = true"
							>
								<History class="w-4 h-4" />
								Rejection History ({{ listing.rejection_reasons.length }})
							</Button>
						</div>
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
		:processing="isApproving"
		@update:show="showApproveDialog = $event"
		@confirm="handleApprove"
		@cancel="showApproveDialog = false"
	/>
	<ConfirmDialog
		:show="showRejectDialog"
		title="Reject Listing"
		description="Please select a reason for rejecting this listing."
		confirmLabel="Reject"
		confirmVariant="destructive"
		:processing="isRejecting"
		:disabled="!selectedReason || (isOtherReason && !customFeedback)"
		showSelect
		:selectOptions="props.rejectionReasons"
		:selectValue="selectedReason"
		:showTextarea="isOtherReason"
		:textareaValue="customFeedback"
		:textareaRequired="isOtherReason"
		textareaPlaceholder="Please provide specific details about why this listing was rejected..."
		@update:show="showRejectDialog = $event"
		@update:selectValue="selectedReason = $event"
		@update:textareaValue="customFeedback = $event"
		@confirm="handleReject"
		@cancel="handleCancelReject"
	/>
	<ConfirmDialog
		:show="showSuspendDialog"
		title="Suspend User Account"
		:description="`Are you sure you want to suspend ${listing.user.name}? This will also mark all their listings as unavailable.`"
		confirmLabel="Suspend User"
		confirmVariant="destructive"
		:processing="isSuspending"
		@update:show="showSuspendDialog = $event"
		@confirm="handleSuspendUser"
		@cancel="showSuspendDialog = false"
	/>
	<ConfirmDialog
		:show="showActivateDialog"
		title="Activate User Account"
		:description="`Are you sure you want to activate ${listing.user.name}'s account? This will allow them to list items and interact with the platform.`"
		confirmLabel="Activate User"
		confirmVariant="default"
		:processing="isActivating"
		@update:show="showActivateDialog = $event"
		@confirm="handleActivateUser"
		@cancel="showActivateDialog = false"
	/>
	<ConfirmDialog
		:show="showTakedownDialog"
		title="Take Down Listing"
		description="Please select a reason for taking down this listing. The owner will be notified."
		confirmLabel="Take Down"
		confirmVariant="destructive"
		:processing="isTakingDown"
		:disabled="!selectedTakedownReason || (isOtherTakedownReason && !takedownFeedback)"
		showSelect
		:selectOptions="takedownReasons"
		:selectValue="selectedTakedownReason"
		:showTextarea="isOtherTakedownReason"
		:textareaValue="takedownFeedback"
		:textareaRequired="isOtherTakedownReason"
		textareaPlaceholder="Please provide specific details about why this listing is being taken down..."
		@update:show="showTakedownDialog = $event"
		@update:selectValue="selectedTakedownReason = $event"
		@update:textareaValue="takedownFeedback = $event"
		@confirm="handleTakedown"
		@cancel="handleCancelTakedown"
	/>

	<Dialog :open="showRejectionHistory" @update:open="showRejectionHistory = $event">
		<DialogContent
			class="w-[calc(100vw-2rem)] sm:w-full sm:max-w-2xl mx-auto h-[calc(100vh-4rem)] sm:h-auto flex flex-col p-0 rounded-lg"
		>
			<DialogHeader class="p-6 border-b">
				<DialogTitle class="flex items-center justify-center gap-2">
					<History class="w-5 h-5" />
					Rejection History
				</DialogTitle>
			</DialogHeader>

			<div class="sm:px-6 flex-1 px-4 py-4 overflow-y-auto">
				<div class="space-y-5">
					<div
						v-for="(rejection, index) in sortedRejectionHistory"
						:key="rejection.pivot.created_at"
					>
						<!-- card entry for each rejection -->
						<div class="bg-muted/50 border rounded-lg">
							<!-- Header -->
							<div class="bg-muted/30 p-3 border-b">
								<div class="flex items-center justify-between gap-2">
									<div class="flex items-center gap-2">
										<AlertCircle class="text-destructive shrink-0 w-5 h-5" />
										<h4 class="line-clamp-1 font-medium">{{ rejection.label }}</h4>
									</div>
								</div>
								<div class="text-muted-foreground flex items-center gap-2 mt-2 text-sm">
									<CalendarClock class="w-4 h-4" />
									<time>{{ rejection.formattedDate }}</time>
								</div>
							</div>

							<!-- Content -->
							<div class="p-3 space-y-3">
								<div class="text-muted-foreground text-sm">
									{{ rejection.description }}
								</div>

								<!-- Separate container for admin feedback -->
								<div class="bg-background p-3 space-y-1 border rounded">
									<!-- Show custom feedback if exists -->
									<template v-if="rejection.feedback">
										<p
											class="text-muted-foreground/70 text-xs font-medium tracking-wider"
										>
											ADMIN FEEDBACK
										</p>
										<p class="text-muted-foreground mb-3 text-sm">
											{{ rejection.feedback }}
										</p>
									</template>

									<!-- Always show admin info -->
									<p
										class="text-muted-foreground text-xs"
										:class="{ 'border-t pt-2': rejection.feedback }"
									>
										Rejected by {{ rejection.adminName }}
									</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</DialogContent>
	</Dialog>
</template>

<style scoped>
/* Add smooth scrollbar for the rejection history */
.overflow-y-auto {
	scrollbar-width: thin;
	scrollbar-color: hsl(var(--muted)) transparent;
}

.overflow-y-auto::-webkit-scrollbar {
	width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
	background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
	background-color: hsl(var(--muted));
	border-radius: 4px;
}

/* Add responsive padding for dialog content */
:deep(.dialog-content) {
	padding: 1rem;
}

@media (min-width: 640px) {
	:deep(.dialog-content) {
		padding: 1.5rem;
	}
}

@media (min-width: 640px) {
	.overflow-y-auto::-webkit-scrollbar {
		width: 6px;
	}
}
</style>
