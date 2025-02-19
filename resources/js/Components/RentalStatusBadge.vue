<script setup>
import { Badge } from "@/components/ui/badge";
import { computed } from "vue";

const props = defineProps({
	status: {
		type: String,
		required: true,
	},
});

const statusInfo = computed(() => {
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
		case "payment_submitted":
			return {
				label: "Payment Submitted",
				variant: "warning",
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
