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

const props = defineProps({
	events: {
		type: Array,
		required: true,
	},
	userRole: {
		type: String,
		required: true,
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
			return "text-destructive";
		case "cancelled":
			return "text-destructive";
		case "handover":
			return "text-emerald-500";
		case "returned":
			return "text-emerald-500";
		default:
			return "text-muted-foreground";
	}
};

const formatEventMessage = (event) => {
	const actor = event.actor.name;
	const isAutoRejection = event.metadata?.auto_rejected;
	const isLatest = event === props.events[0]; // Check if this is the most recent event

	switch (event.event_type) {
		case "created":
			if (isLatest) {
				return props.userRole === "renter"
					? "Waiting for owner's response"
					: `${actor} requested to rent this item`;
			}
			return `${actor} requested to rent this item`;

		case "approved":
			if (isLatest) {
				return props.userRole === "renter"
					? "Ready for handover"
					: "Pending handover to renter";
			}
			return `${actor} approved the rental request`;

		case "rejected":
			if (isAutoRejection) {
				return "Request was automatically rejected because the item was rented for overlapping dates";
			}
			return `${actor} rejected the rental request`;

		case "cancelled":
			return `${actor} cancelled the rental request`;

		case "handover":
			return "Item was handed over to the renter";

		case "returned":
			return "Item was returned to the owner";

		default:
			return `Unknown event by ${actor}`;
	}
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
			<div class="flex gap-4 items-start">
				<!-- Icon -->
				<component
					:is="getEventIcon(event.event_type)"
					class="w-6 h-6 absolute left-0"
					:class="getEventColor(event.event_type)"
				/>

				<!-- Content -->
				<div class="space-y-1">
					<p class="text-sm font-medium">{{ formatEventMessage(event) }}</p>
					<p class="text-muted-foreground text-xs">
						{{ formatDateTime(event.created_at) }}
					</p>

					<!-- Additional Details -->
					<div
						v-if="
							event.metadata &&
							(event.event_type === 'rejected' || event.event_type === 'cancelled')
						"
						class="bg-muted mt-2 p-3 rounded-md text-sm"
					>
						<p class="font-medium text-xs">Reason:</p>
						<p class="text-muted-foreground text-xs mt-1">
							{{ event.metadata.reason }}
						</p>
						<p
							v-if="event.metadata.feedback"
							class="text-muted-foreground text-xs mt-2 italic"
						>
							"{{ event.metadata.feedback }}"
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>
