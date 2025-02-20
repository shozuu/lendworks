<script setup>
import { Badge } from "@/components/ui/badge";
import { computed } from "vue";

const props = defineProps({
	status: {
		type: String,
		required: true,
	},
	paymentRequest: {
		type: Object,
		default: null,
	},
});

const statusInfo = computed(() => {
	if (props.status === "approved" && props.paymentRequest) {
		switch (props.paymentRequest.status) {
			case "pending":
				return {
					label: "Payment Under Review",
					variant: "warning",
				};
			case "rejected":
				return {
					label: "Payment Rejected",
					variant: "destructive",
				};
		}
	}

	switch (props.status) {
		case "pending":
			return {
				label: "Pending",
				variant: "warning",
			};
		case "approved":
			return {
				label: "Approved",
				variant: "info",
			};
		case "to_handover":
			return {
				label: "Payment Verified",
				variant: "success",
			};
		case "active":
			return {
				label: "Active",
				variant: "success",
			};
		case "completed":
			return {
				label: "Completed",
				variant: "default",
			};
		case "rejected":
			return {
				label: "Rejected",
				variant: "destructive",
			};
		case "cancelled":
			return {
				label: "Cancelled",
				variant: "muted",
			};
		default:
			return {
				label: props.status,
				variant: "default",
			};
	}
});
</script>

<template>
	<Badge :variant="statusInfo.variant">
		{{ statusInfo.label }}
	</Badge>
</template>
