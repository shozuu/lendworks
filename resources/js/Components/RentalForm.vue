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
import { calculateRentalPrice } from "@/lib/rentalCalculator";
import { formatNumber } from "@/lib/formatters";
import { ref, reactive, watch, onMounted } from "vue";

const props = defineProps({
	listing: {
		type: Object,
		required: true,
	},
});

const dailyRate = props.listing.price;
const itemValue = props.listing.value;
const rentalDays = ref(7);

const rentalPrice = reactive({
	basePrice: 0,
	discount: 0,
	fee: 0,
	totalPrice: 0,
	discountPercentage: 0,
});

function updateRentalPrice() {
	const result = calculateRentalPrice(dailyRate, itemValue, rentalDays.value);
	Object.assign(rentalPrice, result);
}

function updateRentalDays(startDate, endDate) {
	const start = new Date(startDate);
	const end = new Date(endDate);

	const diffTime = end.getTime() - start.getTime();
	rentalDays.value = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

	updateRentalPrice();
}

watch(rentalDays, (newVal) => {
	updateRentalPrice();
});

onMounted(() => {
	updateRentalPrice(); // Initialize rental price based on default 7 days
});
</script>

<template>
	<Card>
		<CardHeader class="md:p-6 p-4 pt-6">
			<CardTitle> {{ formatNumber(dailyRate) }} per day</CardTitle>
		</CardHeader>

		<CardContent class="md:p-6 md:pt-0 p-4 pt-0">
			<Separator class="my-4" />

			<div class="font-semibold mb-4">Price Range</div>

			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
				<Card
					v-for="days in [3, 7, 30]"
					:key="days"
					class="flex flex-col items-center justify-center"
				>
					<CardHeader>
						<CardTitle class="text-md">{{ days }} Days</CardTitle>
					</CardHeader>
					<CardContent>
						{{
							formatNumber(calculateRentalPrice(dailyRate, itemValue, days).totalPrice)
						}}
					</CardContent>
				</Card>
			</div>

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
						<div>{{ formatNumber(dailyRate) }} x {{ rentalDays }} rental days</div>
						<div>{{ formatNumber(rentalPrice.basePrice) }}</div>
					</div>
					<div class="flex items-center justify-between">
						<div>Duration Discount ({{ rentalPrice.discountPercentage }}%)</div>
						<div>- {{ formatNumber(rentalPrice.discount) }}</div>
					</div>
					<div class="flex items-center justify-between">
						<div>LendWorks Fee</div>
						<div>{{ formatNumber(rentalPrice.fee) }}</div>
					</div>
				</div>
			</div>

			<Separator class="my-4" />

			<div class="flex items-center justify-between font-semibold text-green-500">
				<div>Total</div>
				<div>{{ formatNumber(rentalPrice.totalPrice) }}</div>
			</div>
		</CardContent>

		<CardFooter>
			<Link href="" class="w-full">
				<Button class="w-full"> Send Rent Request </Button>
			</Link>
		</CardFooter>
	</Card>
</template>
