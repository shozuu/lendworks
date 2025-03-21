<script setup>
// Update imports to include formatNumber
import { formatDateTime, formatNumber } from "@/lib/formatters";
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
	Wallet, // Add this import
	PackageOpen, // Add this import
	Check, // Add Check icon import
} from "lucide-vue-next";
import { ref, computed } from "vue";
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

// Add computed property to filter timeline events based on user role
const filteredEvents = computed(() => {
	return props.events.filter((event) => {
		// Allow admin to see all events
		if (props.userRole === "admin") return true;

		// For completion payment events, check user role
		if (event.event_type === "lender_payment_processed") {
			return props.userRole === "lender";
		}
		if (event.event_type === "deposit_refund_processed") {
			return props.userRole === "renter";
		}

		// Show all other events
		return true;
	});
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
		case "rental_completed":
			return CheckCircle2;
		case "lender_payment_processed":
		case "deposit_refund_processed":
			return Wallet;
		case "return_receipt_confirmed":
			return PackageOpen;
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
		case "rental_completed":
			return "text-blue-500";
		case "lender_payment_processed":
		case "deposit_refund_processed":
			return "text-emerald-500";
		case "return_receipt_confirmed":
			return "text-blue-500";
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
				: `${actorLabel} cancelled the request`; // Add semicolon here

		case "receive":
			if (isLatest) {
				return "Item handover completed - rental period has started";
			}
			return performedByViewer
				? "You confirmed receiving the item"
				: `${actorLabel} confirmed receiving the item`; // Fixed missing semicolon

		case "pickup_schedule_selected":
			if (isLatest) {
				const metadata = event.metadata || {};
				const scheduleText = `${metadata.day_of_week}, ${metadata.date} from ${formatTime(
					metadata.start_time
				)} to ${formatTime(metadata.end_time)}`;
				const sourceText = metadata.from_lender_availability
					? "from owner's availability"
					: "as suggested time";

				return performedByViewer
					? `You selected a pickup schedule ${sourceText} for ${scheduleText}`
					: `${actorLabel} selected a pickup schedule ${sourceText} for ${scheduleText}`;
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
			return `${actorLabel} proposed a return schedule for ${formatDateTime(
				event.metadata?.datetime
			)}`;

		case "return_schedule_selected":
			return `${actorLabel} selected a return schedule for ${formatDateTime(
				event.metadata?.datetime
			)}`;

		case "return_schedule_confirmed":
			if (isLatest) {
				const metadata = event.metadata || {};
				const scheduleText = `${metadata.day_of_week}, ${metadata.date} from ${formatTime(
					metadata.start_time
				)} to ${formatTime(metadata.end_time)}`;

				if (performedByViewer) {
					return `You confirmed the return schedule for ${scheduleText}`;
				}

				if (performedByLender) {
					return `The owner confirmed the return schedule for ${scheduleText}`;
				}

				return `${actorLabel} confirmed the return schedule for ${scheduleText}`;
			}
			return `${actorLabel} confirmed the return schedule`;

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
					? `You submitted overdue payment of ${formatNumber(
							props.rental.overdue_fee
					  )} - awaiting verification`
					: `${actorLabel} submitted overdue payment of ${formatNumber(
							props.rental.overdue_fee
					  )} - awaiting verification`;
			}
			return `${actorLabel} submitted overdue payment (Reference: ${event.metadata?.reference_number})`;

		case "overdue_payment_verified":
			if (isLatest) {
				if (props.userRole === "admin") {
					return "Renter's overdue payment has been verified";
				} else if (performedByRenter || props.userRole === "renter") {
					return "Your overdue payment has been verified - you can now proceed with the return process";
				} else if (props.userRole === "lender") {
					return "Renter's overdue payment has been verified - waiting for return process";
				}
			}
			return "Renter's overdue payment was verified";

		case "overdue_payment_rejected":
			if (isLatest) {
				return "Overdue payment was rejected - new payment required";
			}
			return "Overdue payment was rejected";

		case "return_receipt_confirmed":
			if (isLatest) {
				return performedByViewer
					? "You confirmed receiving the item - pending final confirmation"
					: `${actorLabel} confirmed receiving the item - pending final confirmation`;
			}
			return `${actorLabel} confirmed receiving the item`;

		case "rental_completed":
			return "Rental transaction completed - pending final payments";

		case "lender_payment_processed":
			return `Admin processed lender payment (${formatNumber(event.metadata?.amount)})`;

		case "deposit_refund_processed":
			return `Admin processed security deposit refund (${formatNumber(
				event.metadata?.amount
			)})`;

		case "pickup_schedule_suggested":
			if (isLatest) {
				const metadata = event.metadata || {};
				const scheduleText = `${formatDateTime(metadata.datetime)}`;
				return performedByViewer
					? `You suggested a pickup schedule for ${scheduleText}`
					: `${actorLabel} suggested a pickup schedule for ${scheduleText}`;
			}
			return `${actorLabel} suggested a pickup schedule`;

		case "pickup_schedule_suggestion_accepted":
			if (isLatest) {
				const metadata = event.metadata || {};
				const scheduleText = `${metadata.day_of_week}, ${formatDateTime(
					metadata.datetime
				)}`;
				return performedByViewer
					? `You accepted the suggested pickup schedule for ${scheduleText}`
					: `${actorLabel} accepted the pickup schedule for ${scheduleText}`;
			}
			return `${actorLabel} accepted the suggested pickup schedule`;

		case "pickup_schedule_confirmed":
			if (isLatest) {
				const metadata = event.metadata || {};
				const scheduleText = `${metadata.day_of_week}, ${metadata.date} from ${formatTime(
					metadata.start_time
				)} to ${formatTime(metadata.end_time)}`;

				return performedByViewer
					? `You confirmed the pickup schedule for ${scheduleText}`
					: `${actorLabel} confirmed the pickup schedule for ${scheduleText}`;
			}
			return `${actorLabel} confirmed the pickup schedule`;

		default:
			return `Unknown event by ${actorLabel}`;
	}
};

// Add helper function to format time
const formatTime = (timeString) => {
	if (!timeString) return "";
	const [hours, minutes] = timeString.split(":");
	const date = new Date();
	date.setHours(parseInt(hours), parseInt(minutes));
	return date.toLocaleTimeString("en-US", {
		hour: "numeric",
		minute: "2-digit",
		hour12: true,
	});
};

const selectedHistoricalPayment = ref(null);
const showHistoricalPayment = ref(false);

const showPaymentDetails = (event) => {
	if (event.metadata?.payment_request) {
		const isOverduePayment = event.event_type.includes("overdue_payment");
		selectedHistoricalPayment.value = props.passPayment
			? props.passPayment(event)
			: {
					...event.metadata.payment_request,
					rental_request: {
						...props.rental,
						total_price: isOverduePayment
							? props.rental.overdue_fee
							: props.rental.total_price,
						listing: props.rental.listing,
						renter: props.rental.renter,
					},
					amount: isOverduePayment
						? props.rental.overdue_fee
						: event.metadata.payment_request.amount,
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

// Update the showPaymentProof function
const showPaymentProof = (event) => {
	if (event.metadata?.proof_path) {
		// For overdue payments, use the overdue fee amount
		const amount = event.event_type.includes("overdue_payment")
			? props.rental.overdue_fee
			: event.metadata.amount;

		selectedHistoricalPayment.value = {
			proof_path: event.metadata.proof_path,
			reference_number: event.metadata.reference_number,
			amount: amount,
			processed_at: event.created_at,
			type: event.event_type, // Add type to identify overdue payments
		};
		showHistoricalPayment.value = true;
	}
};

// Add helper function for return-related metadata
const showReturnProof = (event) => {
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

// Update phases object with new payment_release phase
const phases = {
	request: {
		title: "Request",
		events: ["created", "approved", "rejected", "cancelled"],
		icon: Send,
		color: "text-blue-500",
		nextPhase: "payment",
	},
	payment: {
		title: "Payment",
		events: ["payment_submitted", "payment_verified", "payment_rejected"],
		icon: DollarSign,
		color: "text-emerald-500",
		nextPhase: "handover",
	},
	handover: {
		title: "Handover",
		events: ["pickup_schedule_selected", "handover", "handover_confirmed", "receive"],
		icon: PackageCheck,
		color: "text-blue-500",
		// Change nextPhase to check for overdue events
		get nextPhase() {
			return hasOverdueEvents.value ? "overdue" : "return";
		},
	},
	overdue: {
		title: "Overdue",
		events: [
			"overdue_payment_submitted",
			"overdue_payment_verified",
			"overdue_payment_rejected",
		],
		icon: Clock,
		color: "text-destructive",
		nextPhase: "return",
	},
	return: {
		title: "Return",
		events: [
			"return_initiated",
			"return_schedule_proposed",
			"return_schedule_selected",
			"return_schedule_confirmed",
			"return_submitted",
			"return_confirmed",
			"return_receipt_confirmed",
		],
		icon: PackageOpen,
		color: "text-yellow-500",
		nextPhase: "completion",
	},
	completion: {
		title: "Completion",
		events: ["rental_completed"],
		icon: CheckCircle2,
		color: "text-emerald-500",
		nextPhase: "payment_release",
	},
	payment_release: {
		title: computed(() =>
			props.userRole === "lender" ? "Payment Release" : "Deposit Refund"
		),
		events: ["lender_payment_processed", "deposit_refund_processed"],
		icon: Wallet,
		color: "text-emerald-500",
		nextPhase: null,
	},
};

// Add completed event types for each phase
const completedEventTypes = {
	request: ["approved"],
	payment: ["payment_verified"],
	handover: ["handover_confirmed", "receive"],
	return: ["return_confirmed", "return_receipt_confirmed"],
	overdue: ["overdue_payment_verified"],
	completion: ["rental_completed"],
	payment_release: ["lender_payment_processed", "deposit_refund_processed"],
};

// Add computed property to check for overdue events
const hasOverdueEvents = computed(() => {
	return filteredEvents.value.some((event) =>
		event.event_type.includes("overdue_payment")
	);
});

// Single definition of eventsByPhase that handles both regular and payment release events
const eventsByPhase = computed(() => {
	const grouped = Object.entries(phases).reduce((acc, [phase, config]) => {
		// Get base events for the phase
		const events = filteredEvents.value.filter((event) =>
			config.events.includes(event.event_type)
		);

		// Handle payment release events specially
		if (phase === "payment_release") {
			acc[phase] = events.filter((event) => {
				if (props.userRole === "lender") {
					return event.event_type === "lender_payment_processed";
				}
				if (props.userRole === "renter") {
					return event.event_type === "deposit_refund_processed";
				}
				return true; // For admin, show all
			});
		} else {
			acc[phase] = events;
		}

		return acc;
	}, {});

	return grouped;
});

// Single definition of hasEvents
const hasEvents = (phase) => (eventsByPhase.value[phase] || []).length > 0;

// Update getPhaseEvents to use eventsByPhase directly
const getPhaseEvents = (phase) => eventsByPhase.value[phase] || [];

// Update isPhaseCompleted to use getPhaseEvents
const isPhaseCompleted = (phase) => {
	const phaseEvents = getPhaseEvents(phase);
	const requiredEvents = completedEventTypes[phase];
	return phaseEvents.some((event) => requiredEvents.includes(event.event_type));
};

// Add state for selected phase
const selectedPhase = ref(null);

// Get active phases (phases that have events)
const activePhases = computed(() =>
	Object.keys(phases).filter((phase) => hasEvents(phase))
);

// Update orderedPhases to place overdue before return
const orderedPhases = computed(() => {
	const basePhases = ["request", "payment", "handover"];
	if (hasOverdueEvents.value) {
		basePhases.push("overdue");
	}
	return [...basePhases, "return", "completion", "payment_release"];
});

// Add function to check if connection line should be highlighted
const isConnectionHighlighted = (phase) => {
	const currentPhase = phase;
	const nextPhase =
		typeof phases[phase].nextPhase === "function"
			? phases[phase].nextPhase()
			: phases[phase].nextPhase;

	if (!nextPhase) return false;

	return isPhaseCompleted(currentPhase);
};
</script>

<template>
	<div class="space-y-6">
		<!-- Phase Timeline - Add container with minimum width -->
		<div class="relative w-full">
			<!-- Timeline Track Container with horizontal scroll -->
			<div class="overflow-x-auto hide-scrollbar sm:overflow-x-visible">
				<div class="min-w-[600px] sm:min-w-0 relative px-4 sm:px-0">
					<!-- Connector lines container -->
					<div class="absolute top-6 left-0 right-0 z-0">
						<!-- Progress bars -->
						<div class="flex items-center">
							<template
								v-for="(phase, index) in orderedPhases"
								:key="`progress-${phase}`"
							>
								<div
									v-if="index < orderedPhases.length - 1"
									class="h-1 flex-1 transition-all duration-500"
									:class="[
										isPhaseCompleted(phase) ? 'bg-emerald-500' : 'bg-border',
										{
											'animate-pulse': hasEvents(phase) && !isPhaseCompleted(phase),
										},
									]"
								></div>
							</template>
						</div>
					</div>

					<!-- Phase nodes -->
					<div class="flex items-center">
						<div
							v-for="phase in orderedPhases"
							:key="phase"
							class="flex-1 flex flex-col items-center gap-2"
						>
							<!-- Rest of the phase node content -->
							<button
								@click="selectedPhase = selectedPhase === phase ? null : phase"
								class="w-12 h-12 rounded-full flex items-center justify-center transition-all relative border-2"
								:class="[
									selectedPhase === phase ? 'bg-muted scale-110' : 'bg-background',
									isPhaseCompleted(phase) ? 'border-emerald-500' : 'border-border',
									hasEvents(phase) ? 'cursor-pointer hover:bg-muted' : 'opacity-50',
								]"
							>
								<component
									:is="phases[phase].icon"
									class="w-5 h-5 transition-colors"
									:class="[
										isPhaseCompleted(phase) ? 'text-emerald-500' : phases[phase].color,
									]"
								/>
								<!-- Event Status Indicator -->
								<div
									v-if="hasEvents(phase) && !isPhaseCompleted(phase)"
									class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-primary flex items-center justify-center"
								>
									<span class="text-[10px] font-medium text-primary-foreground">
										{{ getPhaseEvents(phase).length }}
									</span>
								</div>
							</button>
							<span
								class="text-xs font-medium transition-colors whitespace-nowrap"
								:class="{
									'text-emerald-500': isPhaseCompleted(phase),
									'text-muted-foreground': !hasEvents(phase),
								}"
							>
								{{ phases[phase].title }}
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Event Details - Improve mobile spacing -->
		<div
			v-if="selectedPhase && getPhaseEvents(selectedPhase).length"
			class="mt-6 border rounded-lg p-3 sm:p-4"
		>
			<div class="space-y-4">
				<div
					v-for="event in getPhaseEvents(selectedPhase)"
					:key="event.id"
					class="relative pl-8"
				>
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
							<div v-if="event.metadata">
								<!-- Payment Details -->
								<template
									v-if="
										[
											'payment_submitted',
											'payment_verified',
											'payment_rejected',
										].includes(event.event_type)
									"
								>
									<div class="flex flex-col items-start justify-between">
										<div>
											<p v-if="event.metadata.reference_number" class="text-xs">
												<span class="font-medium">Reference Number:</span>
												{{ event.metadata.reference_number }}
											</p>
											<p v-if="event.metadata.amount" class="text-xs mt-1">
												<span class="font-medium">Amount:</span>
												{{ formatNumber(event.metadata.amount) }}
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

								<!-- Overdue Payment Details -->
								<template
									v-if="
										[
											'overdue_payment_submitted',
											'overdue_payment_verified',
											'overdue_payment_rejected',
										].includes(event.event_type)
									"
								>
									<div class="flex flex-col items-start justify-between">
										<div>
											<p v-if="event.metadata.reference_number" class="text-xs">
												<span class="font-medium">Reference Number:</span>
												{{ event.metadata.reference_number }}
											</p>
											<p class="text-xs mt-1">
												<span class="font-medium">Overdue Payment:</span>
												{{ formatNumber(props.rental.overdue_fee) }}
											</p>
										</div>
									</div>
								</template>

								<!-- Rejection/Cancellation Details -->
								<template
									v-else-if="['rejected', 'cancelled'].includes(event.event_type)"
								>
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
										<Button
											variant="outline"
											size="sm"
											@click="showHandoverDetails(event)"
										>
											View
											{{ event.event_type === "receive" ? "Receive" : "Handover" }} Proof
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
												{{ formatTime(event.metadata.start_time) }} to
												{{ formatTime(event.metadata.end_time) }}
											</span>
										</div>
									</div>
								</template>

								<!-- Add specialized card for payment processing events -->
								<div
									v-if="
										['lender_payment_processed', 'deposit_refund_processed'].includes(
											event.event_type
										)
									"
									class="space-y-2"
								>
									<p class="text-xs">
										<span class="font-medium">Reference Number:</span>
										{{ event.metadata?.reference_number }}
									</p>
									<p class="text-xs">
										<span class="font-medium">Amount:</span>
										{{ formatNumber(event.metadata?.amount) }}
									</p>
								</div>

								<!-- Add specialized card for rental completion -->
								<div
									v-if="event.event_type === 'rental_completed'"
									class="bg-muted mt-2 text-sm rounded-md"
								>
									<div class="space-y-2">
										<div class="flex justify-between items-center">
											<span class="text-muted-foreground">Rental Duration:</span>
											<span class="font-medium"
												>{{ event.metadata?.rental_duration }} days</span
											>
										</div>
										<div class="flex justify-between items-center">
											<span class="text-muted-foreground">Return Date:</span>
											<span class="font-medium">{{
												formatDateTime(event.metadata?.actual_return_date)
											}}</span>
										</div>
									</div>
								</div>

								<!-- Add return proof details -->
								<template
									v-if="
										['return_submitted', 'return_receipt_confirmed'].includes(
											event.event_type
										)
									"
								>
									<div class="bg-muted mt-2 text-sm rounded-md">
										<div class="flex flex-col gap-2">
											<Button variant="outline" size="sm" @click="showReturnProof(event)">
												View
												{{
													event.event_type === "return_receipt_confirmed"
														? "Receive"
														: "Return"
												}}
												Proof
											</Button>
											<p
												v-if="event.metadata?.notes"
												class="text-xs text-muted-foreground italic"
											>
												"{{ event.metadata.notes }}"
											</p>
										</div>
									</div>
								</template>
							</div>
						</div>
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
			@update:show="handleHandoverProofClose"
		/>
	</div>
</template>

<style scoped>
/* Remove the animation classes since they're no longer needed */
.fade-enter-active,
.fade-leave-active {
	transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
	opacity: 0;
}

/* Update progress line animation */
@keyframes pulse {
	0%,
	100% {
		opacity: 0.9;
	}
	50% {
		opacity: 0.2;
	}
}

.animate-pulse {
	animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Update transitions to be smoother */
.fade-enter-active,
.fade-leave-active,
.transition-all {
	transition: all 0.5s ease;
}

/* Add smooth scrolling behavior */
.overflow-x-auto {
	-webkit-overflow-scrolling: touch;
	scrollbar-width: none; /* Firefox */
	-ms-overflow-style: none; /* IE and Edge */
}

/* Hide scrollbar for Chrome/Safari */
.overflow-x-auto::-webkit-scrollbar {
	display: none;
}

/* Add smooth scrolling styles */
.overflow-x-auto {
	-webkit-overflow-scrolling: touch;
}

/* Hide scrollbar but maintain functionality */
.hide-scrollbar {
	scrollbar-width: none; /* Firefox */
	-ms-overflow-style: none; /* IE and Edge */
}

.hide-scrollbar::-webkit-scrollbar {
	display: none; /* Chrome, Safari, Opera */
}

/* Prevent text selection during scroll */
.overflow-x-auto {
	-webkit-user-select: none;
	user-select: none;
}

/* Add momentum-based scrolling for iOS */
@supports (-webkit-overflow-scrolling: touch) {
	.overflow-x-auto {
		-webkit-overflow-scrolling: touch;
	}
}
</style>
