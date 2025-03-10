<script setup>
import { formatDateTime } from "@/lib/formatters";
import {
	CheckCircle2,
	XCircle,
	Clock,
	Send,
	Ban,
	AlertCircle,
	PackageCheck,
	Calendar,
	DollarSign, // Add this import
	CheckCircleIcon, // Add this import
	XCircleIcon, // Add this import
} from "lucide-vue-next";
import { ref } from "vue";
import PaymentDialog from "@/Components/PaymentDialog.vue";
import HandoverProofDialog from "@/Components/HandoverProofDialog.vue";
import { Button } from "@/components/ui/button";

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
		default: null,
	},
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
		case "handover_confirmed":
		case "return_initiated":
		case "return_schedule_proposed":
		case "return_schedule_selected":
		case "return_schedule_confirmed":
		case "return_submitted":
		case "return_confirmed":
			return PackageCheck;
		case "returned":
			return CheckCircle2;
		case "receive":
			return PackageCheck;
		case "pickup_schedule_selected":
			return Calendar;
		case "overdue_payment_submitted":
			return DollarSign;
		case "overdue_payment_verified":
			return CheckCircleIcon;
		case "overdue_payment_rejected":
			return XCircleIcon;
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
		case "handover_confirmed":
		case "returned":
		case "receive":
		case "return_confirmed":
			return "text-emerald-500";
		case "pickup_schedule_selected":
		case "return_initiated":
		case "return_schedule_proposed":
		case "return_schedule_selected":
		case "return_schedule_confirmed":
			return "text-blue-500";
		case "return_submitted":
			return "text-yellow-500";
		case "overdue_payment_submitted":
			return "text-yellow-500";
		case "overdue_payment_verified":
			return "text-emerald-500";
		case "overdue_payment_rejected":
			return "text-destructive";
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
					? "You submitted a rental request - awaiting owner's approval"
					: `${actorLabel} submitted a rental request${
							performedByRenter ? " - awaiting owner's approval" : ""
					  }`;
			}
			return `${actorLabel} submitted a rental request`;

		case "approved":
			if (isLatest) {
				if (props.rental.payment_request) {
					return performedByViewer
						? "You approved the request - payment verification in progress"
						: `${actorLabel} approved the request - payment verification in progress`;
				}
				return performedByViewer
					? "You approved the request - awaiting payment"
					: `${actorLabel} approved the request${
							performedByLender ? " - awaiting payment" : ""
					  }`;
			}
			return `${actorLabel} approved the rental request`;

		case "payment_submitted":
			if (isLatest) {
				return performedByViewer
					? "You submitted the payment - awaiting verification"
					: `${actorLabel} submitted payment${
							performedByRenter ? " - awaiting verification" : ""
					  }`;
			}
			return `${actorLabel} submitted payment (Reference: ${event.metadata?.reference_number})`;

		case "payment_verified":
			if (isLatest) {
				if (performedByRenter || props.userRole === "renter") {
					return "Payment verified - waiting to receive item";
				}
				return "Payment verified - proceed with item handover";
			}
			return "Payment was verified";

		case "payment_rejected":
			if (isLatest) {
				return "Payment was rejected - new payment required";
			}
			return "Payment was rejected";

		case "handover":
			if (isLatest) {
				if (performedByLender || props.userRole === "lender") {
					return "Item handed over - awaiting renter's confirmation";
				}
				return "Item received - awaiting owner's confirmation";
			}
			return performedByViewer
				? `You ${performedByRenter ? "received" : "handed over"} the item`
				: `${actorLabel} ${performedByRenter ? "received" : "handed over"} the item`;

		case "handover_confirmed":
			if (isLatest) {
				return "Rental is now active - item handover completed";
			}
			return performedByViewer
				? "You confirmed the handover"
				: `${actorLabel} confirmed the handover`;

		case "returned":
			if (isLatest) {
				return "Rental completed - item returned successfully";
			}
			return performedByViewer
				? "You confirmed returning the item"
				: `${actorLabel} confirmed returning the item`;

		case "rejected":
			if (isAutoRejection) {
				return "Request was automatically rejected due to date conflict";
			}
			return performedByViewer
				? "You rejected the request"
				: `${actorLabel} rejected the request`;

		case "cancelled":
			return performedByViewer
				? "You cancelled the request"
				: `${actorLabel} cancelled the request`;

		case "receive":
			if (isLatest) {
				return "Item handover completed - rental period has started";
			}
			return performedByViewer
				? `You confirmed receiving the item`
				: `${actorLabel} confirmed receiving the item`;

		case "pickup_schedule_selected":
			if (isLatest) {
				const metadata = event.metadata || {};
				return performedByViewer
					? `You selected a pickup schedule for ${metadata.day_of_week}, ${metadata.date} from ${formatTime(metadata.start_time)} to ${formatTime(metadata.end_time)}`
					: `${actorLabel} selected a pickup schedule for ${metadata.day_of_week}, ${metadata.date} from ${formatTime(metadata.start_time)} to ${formatTime(metadata.end_time)}`;
			}
			return `${actorLabel} selected a pickup schedule`;

		case "return_initiated":
			if (isLatest) {
				return performedByViewer
					? "You initiated the return process"
					: `${actorLabel} initiated the return process`;
			}
			return `${actorLabel} initiated the return process`;

		case "return_schedule_proposed":
			return `${actorLabel} proposed a return schedule for ${formatDateTime(event.metadata?.datetime)}`;

		case "return_schedule_selected":
			return `${actorLabel} selected a return schedule for ${formatDateTime(event.metadata?.datetime)}`;

		case "return_schedule_confirmed":
			return `${actorLabel} confirmed the return schedule for ${formatDateTime(event.metadata?.datetime)}`;

		case "return_submitted":
			if (isLatest) {
				return performedByViewer
					? "You submitted return proof - awaiting confirmation"
					: `${actorLabel} submitted return proof - awaiting confirmation`;
			}
			return `${actorLabel} submitted return proof`;

		case "return_confirmed":
			if (isLatest) {
				return "Rental completed - item returned successfully";
			}
			return `${actorLabel} confirmed the return`;

		case "overdue_payment_submitted":
			if (isLatest) {
				return performedByViewer
					? "You submitted overdue payment - awaiting verification"
					: `${actorLabel} submitted overdue payment - awaiting verification`;
			}
			return `${actorLabel} submitted overdue payment (Reference: ${event.metadata?.reference_number})`;

		case "overdue_payment_verified":
			if (isLatest) {
				return "Overdue payment verified - you can now proceed with return process";
			}
			return "Overdue payment was verified";

		case "overdue_payment_rejected":
			if (isLatest) {
				return "Overdue payment was rejected - new payment required";
			}
			return "Overdue payment was rejected";

		default:
			return `Unknown event by ${actorLabel}`;
	}
};

// Add helper function to format time
const formatTime = (timeString) => {
	if (!timeString) return '';
	const [hours, minutes] = timeString.split(':');
	const date = new Date();
	date.setHours(parseInt(hours), parseInt(minutes));
	return date.toLocaleTimeString('en-US', { 
		hour: 'numeric',
		minute: '2-digit',
		hour12: true 
	});
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
						renter: props.rental.renter,
					},
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

// Add new refs for handover proof dialog
const showHandoverProof = ref(false);
const selectedHandoverProof = ref(null);

// Add handler for showing handover proof
const showHandoverDetails = (event) => {
	if (event.metadata?.proof_path) {
		selectedHandoverProof.value = {
			path: event.metadata.proof_path,
			type: event.event_type,
			performer: event.actor,
			timestamp: event.created_at,
		};
		showHandoverProof.value = true;
	}
};

// Add handler for dialog close
const handleHandoverProofClose = () => {
	showHandoverProof.value = false;
	setTimeout(() => {
		selectedHandoverProof.value = null;
	}, 300);
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
								</div>
							</div>
							<p
								v-if="event.metadata.feedback"
								class="text-muted-foreground mt-2 text-xs italic"
							>
								"{{ event.metadata.feedback }}"
							</p>
							<!-- Add View Payment Details button if we have payment data -->
							<Button
								v-if="event.metadata.payment_request"
								variant="outline"
								size="sm"
								class="mt-2"
								@click="showPaymentDetails(event)"
							>
								View Payment Details
							</Button>
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

						<!-- Handover Details -->
						<template v-if="['handover', 'receive'].includes(event.event_type)">
							<div class="flex flex-col gap-2">
								<Button variant="outline" size="sm" @click="showHandoverDetails(event)">
									View {{ event.event_type === "receive" ? "Receive" : "Handover" }} Proof
								</Button>
							</div>
						</template>

						<!-- Add Pickup Schedule Details -->
						<template v-if="event.event_type === 'pickup_schedule_selected'">
							<div class="space-y-2 text-xs">
								<div class="flex items-baseline gap-1">
									<span class="font-medium">Pickup Schedule:</span>
									<span class="text-muted-foreground">
										{{ event.metadata.day_of_week }}, {{ event.metadata.date }}
									</span>
								</div>
								<div class="flex items-baseline gap-1">
									<span class="font-medium">Time:</span>
									<span class="text-muted-foreground">
										{{ formatTime(event.metadata.start_time) }} to {{ formatTime(event.metadata.end_time) }}
									</span>
								</div>
							</div>
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
			:viewOnly="true"
			@update:show="handleDialogClose"
		/>

		<!-- Handover Proof Dialog -->
		<HandoverProofDialog
			:show="showHandoverProof"
			:imagePath="selectedHandoverProof?.path"
			:type="selectedHandoverProof?.type"
			:performer="selectedHandoverProof?.performer"
			:timestamp="selectedHandoverProof?.timestamp"
			:onClose="handleHandoverProofClose"
		/>
	</div>
</template>
