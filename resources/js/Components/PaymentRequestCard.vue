<script setup>
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { formatDate } from "@/lib/formatters";

defineProps({
	payment: {
		type: Object,
		required: true,
	},
});
</script>

<template>
	<Card class="hover:bg-muted p-6 transition-colors">
		<div class="flex items-start justify-between gap-4">
			<div class="space-y-1">
				<h3 class="font-medium">Reference #{{ payment.reference_number }}</h3>
				<div class="text-muted-foreground text-sm">
					<p>Submitted: {{ formatDate(payment.created_at) }}</p>
					<p>Rental Request: #{{ payment.rental_request_id }}</p>
					<p>Renter: {{ payment.rental_request.renter.name }}</p>
					<p>Amount: ₱{{ payment.rental_request.total_price }}</p>
				</div>
			</div>

			<Badge
				:variant="
					payment.status === 'pending'
						? 'warning'
						: payment.status === 'verified'
						? 'success'
						: 'destructive'
				"
			>
				{{ payment.status.charAt(0).toUpperCase() + payment.status.slice(1) }}
			</Badge>
		</div>
	</Card>
</template>
