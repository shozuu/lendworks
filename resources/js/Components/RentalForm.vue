<script setup>
import {
	Card,
	CardContent,
	CardDescription,
	CardFooter,
	CardHeader,
	CardTitle,
} from "@/components/ui/card";
import Separator from "@/Components/ui/separator/Separator.vue";
import Button from "@/Components/ui/button/Button.vue";
import RentalDatesPicker from "./RentalDatesPicker.vue";
import { calculateRentalPrice, formatCurrency } from "@/rentalCalculator";
import { ref, reactive, watch, onMounted } from "vue";

const props = defineProps({
	listing: {
		type: Object,
		required: true,
	},
});

const itemValue = props.listing.price; // Assuming the item value is provided in the listing
const rentalDays = ref(7); // Default rental days

const rentalPrice = reactive({
	basePrice: 0,
	discount: 0,
	fee: 0,
	totalPrice: 0,
});

function updateRentalPrice() {
	const result = calculateRentalPrice(itemValue, rentalDays.value);
	console.log("Rental Price Calculation:", result);
	rentalPrice.basePrice = result.basePrice;
	rentalPrice.discount = result.discount;
	rentalPrice.fee = result.fee;
	rentalPrice.totalPrice = result.totalPrice;
}

function updateRentalDays(startDate, endDate) {
	const diffTime = Math.abs(new Date(endDate) - new Date(startDate));
	rentalDays.value = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
	console.log("Updated Rental Days:", rentalDays.value);
	updateRentalPrice();
}

watch(rentalDays, (newVal) => {
	console.log("Rental Days Changed:", newVal);
	updateRentalPrice();
});

onMounted(() => {
	updateRentalPrice(); // Initialize rental price based on default 7 days
});
</script>

<template>
	<Card>
		<CardHeader class="md:p-6 p-4 pt-6">
			<CardTitle> {{ formatCurrency(itemValue) }} per day</CardTitle>
		</CardHeader>

		<CardContent class="md:p-6 md:pt-0 p-4 pt-0">
			<Separator class="my-4" />

			<div class="text-foreground text-base font-semibold">Price Range</div>
			<br />
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
				<Card class="flex flex-col items-center justify-center">
					<CardHeader>
						<CardTitle class="text-md">3 Days</CardTitle>
					</CardHeader>
					<CardContent class="flex flex-col items-center justify-center">
						{{ formatCurrency(calculateRentalPrice(itemValue, 3).basePrice) }}
					</CardContent>
				</Card>
				<Card class="flex flex-col items-center justify-center">
					<CardHeader>
						<CardTitle class="text-md">7 Days</CardTitle>
					</CardHeader>
					<CardContent class="flex flex-col items-center justify-center">
						{{ formatCurrency(calculateRentalPrice(itemValue, 7).basePrice) }}
					</CardContent>
				</Card>
				<Card class="flex flex-col items-center justify-center">
					<CardHeader>
						<CardTitle class="text-md">30 Days</CardTitle>
					</CardHeader>
					<CardContent class="flex flex-col items-center justify-center">
						{{ formatCurrency(calculateRentalPrice(itemValue, 30).basePrice) }}
					</CardContent>
				</Card>
			</div>

			<p class="text-center text-sm mt-4">
				Select dates in the calendar to see the exact price.
			</p>

			<Separator class="my-4" />

			<div class="space-y-2">
				<div class="font-semibold">Rental Dates</div>
				<RentalDatesPicker @update-dates="updateRentalDays" />
			</div>

			<Separator class="my-4" />

			<div class="text-muted-foreground space-y-2 text-sm">
				<div class="text-foreground text-base font-semibold">Rental Summary</div>

				<div class="space-y-1">
					<div class="flex items-center justify-between">
						<div>{{ formatCurrency(itemValue) }} x {{ rentalDays }} rental days</div>
						<div>{{ formatCurrency(rentalPrice.basePrice) }}</div>
					</div>
					<div class="flex items-center justify-between">
						<div>
							Duration Discount ({{
								(rentalPrice.discount / rentalPrice.basePrice) * 100
							}}%)
						</div>
						<div>- {{ formatCurrency(rentalPrice.discount) }}</div>
					</div>
					<div class="flex items-center justify-between">
						<div>LendWorks Fee (25%)</div>
						<div>{{ formatCurrency(rentalPrice.fee) }}</div>
					</div>
				</div>
			</div>

			<Separator class="my-4" />

			<div class="flex items-center justify-between font-semibold text-green-500">
				<div>Total</div>
				<div>{{ formatCurrency(rentalPrice.totalPrice) }}</div>
			</div>
		</CardContent>

		<CardFooter>
			<Link href="" class="w-full">
				<Button class="w-full"> Send Rent Request </Button>
			</Link>
		</CardFooter>
	</Card>
</template>
