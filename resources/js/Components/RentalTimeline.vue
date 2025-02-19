<script setup>
import { formatDateTime } from "@/lib/formatters";
import {
	CheckCircle2,
	XCircle,
	Circle,
	Clock,
	Send,
	Ban,
	AlertCircle,
	} from "lucide-vue-next";
import { ref } from "vue";
import PaymentDialog from "@/Components/PaymentDialog.vue";

const props = defineProps({
	events: {
		type: Array,
		required: true,
	},
	userRole: {
		type: String,
		required: true,
	},
	rental: {
		type: Object,
		required: true,
	},
	passPayment: {
		type: Function,
		default: null
	}
});

const getEventIcon = (eventType) => {
	switch (eventType) {
		case "created":
			return Send;
		case "approved":
			return CheckCircle2;
		case "rejected":
			return XCircle;
		case "cancelled":
			return Ban;
		case "payment_submitted":
			return Clock;
		case "payment_verified":
			return CheckCircle2;
		case "payment_rejected":
			return XCircle;
		case "handover":
			return Circle;
		case "returned":
			return CheckCircle2;
		default:
			return AlertCircle;
	}
};

const getEventColor = (eventType) => {
	switch (eventType) {
		case "created":
			return "text-blue-500";
		case "approved":
			return "text-emerald-500";
		case "rejected":
		case "cancelled":
		case "payment_rejected":
			return "text-destructive";
		case "payment_submitted":
			return "text-yellow-500";
		case "payment_verified":
		case "handover":
		case "returned":
			return "text-emerald-500";
		default:
			return "text-muted-foreground";
	}
};

const formatEventMessage = (event) => {
	const actor = event.actor.name;
	const isAutoRejection = event.metadata?.auto_rejected;
	const isLatest = event === props.events[0];
	const performedByRenter = event.actor_id === props.rental?.renter_id;
	const performedByLender = event.actor_id === props.rental?.listing?.user_id;
	const performedByViewer = event.actor_id === props.rental?.viewer_id;

	const getActorLabel = () => {
		if (performedByViewer) return "You";
		if (performedByRenter) return props.userRole === "renter" ? "You" : "The renter";
		if (performedByLender) return props.userRole === "lender" ? "You" : "The owner";
		return actor;
	};

	const actorLabel = getActorLabel();

	switch (event.event_type) {
		case "created":
			if (isLatest) {
				return performedByViewer
					? "You submitted a rental request - awaiting owner's response"
					: `${actorLabel} submitted a rental request${
							performedByRenter ? " - awaiting owner's response" : ""
					  }`;
			}
			return `${actorLabel} submitted a rental request`;

		case "approved":
			if (isLatest) {
				return performedByViewer
					? "You approved the request - waiting for renter's payment"
					: `${actorLabel} approved the request${
							performedByLender ? " - waiting for payment" : ""
					  }`;
			}
			return `${actorLabel} approved the rental request`;

		case "payment_submitted":
			if (isLatest) {
				return performedByViewer
					? "Your payment has been submitted - awaiting verification"
					: `${actorLabel} submitted payment${
							performedByRenter ? " - awaiting verification" : ""
					  }`;
			}
			return `${actorLabel} submitted payment (Reference: ${event.metadata?.reference_number})`;

		case "payment_verified":
			return "Payment was verified by admin";

		case "payment_rejected":
			return "Payment was rejected by admin";

		case "rejected":
			if (isAutoRejection) {
				return "Request was automatically rejected due to date conflict";
			}
			return performedByViewer
				? "You rejected the request"
				: `${actorLabel} rejected the request`;

		case "cancelled":
			const cancelledBy = event.metadata?.cancelled_by;
			if (performedByViewer) {
				return "You cancelled the request";
			}
			return `${actorLabel} cancelled the request`;

		case "handover":
			return performedByViewer
				? "You confirmed receiving the item"
				: `${actorLabel} confirmed receiving the item`;

		case "returned":
			return performedByViewer
				? "You confirmed returning the item"
				: `${actorLabel} confirmed returning the item`;

		default:
			return `Unknown event by ${actorLabel}`;
	}
};

const selectedHistoricalPayment = ref(null);
const showHistoricalPayment = ref(false);

const showPaymentDetails = (event) => {
    if (event.metadata?.payment_request) {
        selectedHistoricalPayment.value = props.passPayment 
            ? props.passPayment(event)
            : {
                ...event.metadata.payment_request,
                rental_request: {
                    ...props.rental,
                    total_price: props.rental.total_price,
                    listing: props.rental.listing,
                    renter: props.rental.renter
                }
            };
        showHistoricalPayment.value = true;
    }
};

// Add handler for dialog close
const handleDialogClose = () => {
    // Wait for dialog animation to complete before clearing data
    setTimeout(() => {
        selectedHistoricalPayment.value = null;
    }, 300);
    showHistoricalPayment.value = false;
};
</script>

<template>
	<div class="space-y-6">
		<div v-for="event in events" :key="event.id" class="relative pl-8">
			<!-- Connector Line -->
			<div
				v-if="!event.isLast"
				class="absolute left-[11px] top-[24px] h-full w-[2px] bg-border"
			></div>

			<!-- Event Item -->
			<div class="flex items-start gap-4">
				<!-- Icon -->
				<component
					:is="getEventIcon(event.event_type)"
					class="absolute left-0 w-6 h-6"
					:class="getEventColor(event.event_type)"
				/>

				<!-- Content -->
				<div class="space-y-1">
					<p class="text-sm font-medium">{{ formatEventMessage(event) }}</p>
					<p class="text-muted-foreground text-xs">
						{{ formatDateTime(event.created_at) }}
					</p>

					<!-- Additional Details -->
					<div v-if="event.metadata" class="bg-muted p-3 mt-2 text-sm rounded-md">
						<!-- Payment Details -->
						<template
							v-if="
								['payment_submitted', 'payment_verified', 'payment_rejected'].includes(
									event.event_type
								)
							"
						>
							<div class="flex flex-col items-start justify-between">
								<div>
									<p v-if="event.metadata.reference_number" class="text-xs">
										<span class="font-medium">Reference Number:</span>
										{{ event.metadata.reference_number }}
									</p>
									<p v-if="event.metadata.verified_by" class="mt-1 text-xs">
										<span class="font-medium">Verified by:</span>
										{{ event.metadata.verified_by }}
									</p>
								</div>
								<!-- Add View Payment Details button if we have payment data -->
								<Button 
									v-if="event.metadata.payment_request"
									variant="outline" 
									size="sm"
									@click="showPaymentDetails(event)"
								>
									View Payment Details
								</Button>
							</div>
							<p
								v-if="event.metadata.feedback"
								class="text-muted-foreground mt-2 text-xs italic"
							>
								"{{ event.metadata.feedback }}"
							</p>
						</template>

						<!-- Rejection/Cancellation Details -->
						<template v-else-if="['rejected', 'cancelled'].includes(event.event_type)">
							<p class="text-xs font-medium">Reason:</p>
							<p class="text-muted-foreground mt-1 text-xs">
								{{ event.metadata.reason }}
							</p>
							<p
								v-if="event.metadata.feedback"
								class="text-muted-foreground mt-2 text-xs italic"
							>
								"{{ event.metadata.feedback }}"
							</p>
						</template>
					</div>
				</div>
			</div>
		</div>

		<!-- Payment Dialog for Historical Payments -->
		<PaymentDialog 
			:show="showHistoricalPayment"
			:rental="rental"
			:payment="selectedHistoricalPayment"
			:historical="true"
			@update:show="handleDialogClose"
		/>
	</div>
</template>
