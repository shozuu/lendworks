<script setup>
import { Badge } from "@/components/ui/badge";
import { computed } from "vue";

const props = defineProps({
	status: {
		type: String,
		required: true,
	},
	isAvailable: {
		type: Boolean,
		default: true,
	},
});

const statusInfo = computed(() => {
	switch (props.status) {
		case "approved":
			return {
				label: props.isAvailable ? "Available" : "Not Available",
				variant: props.isAvailable ? "success" : "destructive",
			};
		case "rejected":
			return {
				label: "Rejected",
				variant: "destructive",
			};
		case "taken_down":
			return {
				label: "Taken Down",
				variant: "destructive",
			};
		case "pending":
		default:
			return {
				label: "Pending Approval",
				variant: "warning",
			};
	}
});
</script>

<template>
	<Badge :variant="statusInfo.variant" class="whitespace-nowrap shrink-0">
		{{ statusInfo.label }}
	</Badge>
</template>
