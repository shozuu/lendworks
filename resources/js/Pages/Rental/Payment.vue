<script setup>
import { Head, router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import {
	Card,
	CardContent,
	CardFooter,
	CardHeader,
	CardTitle,
} from "@/components/ui/card";
import { formatNumber } from "@/lib/formatters";

const props = defineProps({
	rental: {
		type: Object,
		required: true,
	},
});
import { useForm } from "@inertiajs/vue3";

const form = useForm({});

const handleSubmitPayment = () => {
	form.post(route("rentals.submit-payment", props.rental.id), {
		preserveScroll: true,
		onError: () => {
			// Error is handled by Inertia's error bag
		},
	});
};

const rejectPayment = (rentalId) => {
	router.patch(route("admin.payments.reject", rentalId));
};
</script>

<template>
	<Head title="Payment" />

	<Card class="max-w-2xl mx-auto">
		<CardHeader>
			<CardTitle>Payment Details</CardTitle>
		</CardHeader>

		<CardContent class="space-y-4">
			<div class="space-y-2">
				<h3 class="font-semibold">{{ rental.listing.title }}</h3>
				<div class="text-muted-foreground text-sm">
					<p>Owner: {{ rental.listing.user.name }}</p>
					<p class="mt-1">Total Amount: {{ formatNumber(rental.total_price) }}</p>
				</div>
			</div>

			<!-- Add payment instructions -->
			<div class="space-y-2 text-sm">
				<p class="font-semibold">Payment Instructions:</p>
				<ol class="text-muted-foreground space-y-1 list-decimal list-inside">
					<li>Send the exact amount to our GCash number: 09XX-XXX-XXXX</li>
					<li>Use the rental ID as reference: #{{ rental.id }}</li>
					<li>Keep your payment receipt</li>
					<li>Click "Submit Payment" below once done</li>
				</ol>
			</div>
		</CardContent>

		<CardFooter>
			<Button class="w-full" @click="handleSubmitPayment">Submit Payment</Button>
		</CardFooter>
	</Card>
</template>
