<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router, Link } from "@inertiajs/vue3";
import { Badge } from "@/components/ui/badge";
import { formatDate } from "@/lib/formatters";
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogHeader,
	DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import PaginationLinks from "@/Components/PaginationLinks.vue";
import { ref } from "vue";
import { ChevronRight } from "lucide-vue-next";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import PaymentRequestCard from "@/Components/PaymentRequestCard.vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	payments: Object,
	stats: Object,
});

const selectedPayment = ref(null);
const showVerifyDialog = ref(false);
const showRejectDialog = ref(false);
const rejectionFeedback = ref("");
const rejectionError = ref("");
const selectedForAction = ref(null);

const closeDialog = () => {
	selectedPayment.value = null;
};

const openVerifyDialog = (payment) => {
	selectedPayment.value = null; // Close details dialog
	setTimeout(() => {
		selectedForAction.value = payment;
		showVerifyDialog.value = true;
	}, 100);
};

const openRejectDialog = (payment) => {
	selectedPayment.value = null; // Close details dialog
	setTimeout(() => {
		selectedForAction.value = payment;
		showRejectDialog.value = true;
		rejectionFeedback.value = "";
	}, 100);
};

const handleVerify = () => {
	router.post(
		route("admin.payments.verify", selectedForAction.value.id),
		{},
		{
			onSuccess: () => {
				showVerifyDialog.value = false;
				selectedForAction.value = null;
			},
		}
	);
};

const handleReject = () => {
	if (rejectionFeedback.value.trim().length < 10) {
		rejectionError.value = "Feedback must be at least 10 characters long";
		return;
	}

	router.post(
		route("admin.payments.reject", selectedForAction.value.id),
		{ feedback: rejectionFeedback.value },
		{
			onSuccess: () => {
				showRejectDialog.value = false;
				selectedPayment.value = null;
				selectedForAction.value = null;
				rejectionFeedback.value = "";
				rejectionError.value = "";
			},
		}
	);
};
</script>

<template>
	<Head title="| Admin - Payment Requests" />

	<div class="space-y-6">
		<!-- Header Section -->
		<div class="flex flex-col gap-4">
			<div class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-2">
				<div class="space-y-1">
					<h2 class="text-2xl font-semibold tracking-tight">Payment Requests</h2>
					<p class="text-muted-foreground text-sm">
						Review and verify payment submissions
					</p>
				</div>

				<!-- Stats badge -->
				<div class="flex flex-wrap gap-2">
					<Badge variant="outline">Total: {{ stats.total }}</Badge>
					<Badge variant="warning">Pending: {{ stats.pending }}</Badge>
					<Badge variant="success">Verified: {{ stats.verified }}</Badge>
					<Badge variant="destructive">Rejected: {{ stats.rejected }}</Badge>
				</div>
			</div>
		</div>

		<!-- Payment Cards -->
		<div v-if="payments.data.length" class="space-y-4">
			<template v-for="payment in payments.data" :key="payment.id">
				<!-- Use div for pending payments to show dialog -->
				<div
					v-if="payment.status === 'pending'"
					class="block cursor-pointer"
					@click="selectedPayment = payment"
				>
					<PaymentRequestCard :payment="payment" />
				</div>

				<!-- Use Link for non-pending payments to navigate -->
				<Link
					v-else
					:href="route('admin.rental-transactions.show', payment.rental_request_id)"
					class="block"
				>
					<PaymentRequestCard :payment="payment" />
				</Link>
			</template>
			<PaginationLinks :paginator="payments" />
		</div>
		<div v-else class="text-muted-foreground py-10 text-center">
			No payment requests found
		</div>
	</div>

	<!-- Payment Details Dialog -->
	<Dialog :open="!!selectedPayment" @update:open="closeDialog">
		<DialogContent class="sm:max-w-xl">
			<DialogHeader>
				<DialogTitle class="flex items-center gap-2">
					Payment Request Details
					<Badge 
						v-if="selectedPayment?.type === 'overdue'"
						variant="destructive"
					>
						Overdue Fee
					</Badge>
				</DialogTitle>
				<DialogDescription v-if="selectedPayment">
					Reference #{{ selectedPayment.reference_number }}
					<p>
						<span class="font-medium">Submitted:</span>
						{{ formatDate(selectedPayment.created_at) }}
					</p>
					<!-- Add payment type info -->
					<p v-if="selectedPayment.type === 'overdue'" class="text-destructive mt-1">
						This is an overdue fee payment for late return
					</p>
				</DialogDescription>
			</DialogHeader>

			<div v-if="selectedPayment" class="space-y-4">
				<!-- Rental Context Section -->
				<div class="bg-muted p-4 space-y-3 rounded-lg">
					<div class="flex items-start gap-4">
						<!-- Listing Image -->
						<img
							:src="
								selectedPayment.rental_request.listing.images[0]?.image_path
									? `/storage/${selectedPayment.rental_request.listing.images[0].image_path}`
									: '/storage/images/listing/default.png'
							"
							class="object-cover w-20 h-20 rounded-md"
							:alt="selectedPayment.rental_request.listing.title"
						/>
						<div class="flex-1 min-w-0">
							<h4 class="font-medium truncate">
								{{ selectedPayment.rental_request.listing.title }}
							</h4>
							<div class="text-muted-foreground space-y-1 text-sm">
								<p>
									<span class="font-medium">Lender:</span>
									{{ selectedPayment.rental_request.listing.user.name }}
								</p>
								<p>
									<span class="font-medium">Renter:</span>
									{{ selectedPayment.rental_request.renter.name }}
								</p>
								<p>
									<span class="font-medium">Total Price:</span> ₱{{
										selectedPayment.rental_request.total_price
									}}
								</p>
							</div>
						</div>
					</div>
					<Link
						:href="
							route('admin.rental-transactions.show', selectedPayment.rental_request.id)
						"
						class="text-primary hover:underline inline-flex items-center gap-1 text-sm"
					>
						View Rental Transaction Details
						<ChevronRight class="w-4 h-4" />
					</Link>
				</div>

				<!-- Payment Proof Image -->
				<div class="space-y-2">
					<h4 class="text-sm font-medium">Payment Screenshot</h4>
					<div class="aspect-video overflow-hidden border rounded-lg">
						<img
							:src="`/storage/${selectedPayment.payment_proof_path}`"
							:alt="'Payment proof for ' + selectedPayment.reference_number"
							class="object-contain w-full h-full"
						/>
					</div>
				</div>

				<!-- Action Buttons -->
				<div class="flex justify-end gap-2 pt-4">
					<Button variant="destructive" @click="openRejectDialog(selectedPayment)">
						Reject
					</Button>
					<Button @click="openVerifyDialog(selectedPayment)"> Verify Payment </Button>
				</div>
			</div>
		</DialogContent>
	</Dialog>

	<!-- Verify Dialog -->
	<ConfirmDialog
		:show="showVerifyDialog"
		title="Verify Payment"
		description="Are you sure you want to verify this payment? This will update the rental status to Ready for Handover."
		confirmLabel="Verify Payment"
		confirmVariant="default"
		@update:show="showVerifyDialog = $event"
		@confirm="handleVerify"
		@cancel="showVerifyDialog = false"
	/>

	<!-- Reject Dialog -->
	<ConfirmDialog
		:show="showRejectDialog"
		title="Reject Payment"
		description="Please provide detailed feedback about why this payment proof is being rejected."
		confirmLabel="Reject Payment"
		confirmVariant="destructive"
		:disabled="rejectionFeedback.trim().length < 10"
		:forceShowTextarea="true"
		:textareaValue="rejectionFeedback"
		textareaRequired
		textareaMinLength="10"
		:textAreaError="rejectionError"
		textareaPlaceholder="Enter detailed feedback for the renter..."
		@update:show="
			(val) => {
				showRejectDialog = val;
				if (!val) rejectionError = '';
			}
		"
		@update:textareaValue="
			(val) => {
				rejectionFeedback = val;
				rejectionError = '';
			}
		"
		@confirm="handleReject"
		@cancel="
			() => {
				showRejectDialog = false;
				rejectionError = '';
			}
		"
	/>
</template>
