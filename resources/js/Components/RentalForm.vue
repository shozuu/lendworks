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
import { useForm as useInertiaForm } from "@inertiajs/vue3";
import { format } from "date-fns"; // Add this import

const props = defineProps({
	listing: {
		type: Object,
		required: true,
	},
	isOwner: {
		type: Boolean,
		default: false,
	},
});

const dailyRate = props.listing.price;
const itemValue = props.listing.value;
const rentalDays = ref(7);

const rentalPrice = reactive({
	basePrice: 0,
	discountPercentage: 0,
	discount: 0,
	fee: 0,
	totalPrice: 0,
});

const rentalForm = useInertiaForm({
	listing_id: props.listing.id,
	start_date: null,
	end_date: null,
	base_price: 0,
	discount: 0,
	service_fee: 0,
	total_price: 0,
});

const selectedDates = ref({
	start: null,
	end: null,
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

watch(
	selectedDates,
	(newVal) => {
		if (newVal.start && newVal.end) {
			rentalForm.start_date = format(new Date(newVal.start), "yyyy-MM-dd");
			rentalForm.end_date = format(new Date(newVal.end), "yyyy-MM-dd");
			updateRentalDays(newVal.start, newVal.end);
			rentalForm.base_price = rentalPrice.basePrice;
			rentalForm.discount = rentalPrice.discount;
			rentalForm.service_fee = rentalPrice.fee;
			rentalForm.total_price = rentalPrice.totalPrice;
		}
	},
	{ deep: true }
);

const handleSubmit = () => {
	if (!selectedDates.value.start || !selectedDates.value.end) {
		return;
	}

	rentalForm.post(route("rentals.store"), {
		onSuccess: () => {
			// Reset form
			selectedDates.value = { start: null, end: null };
			rentalForm.reset();
		},
		preserveScroll: true,
	});
};

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

			<template v-if="!isOwner">
				<div v-if="!$page.props.auth.user" class="text-center text-muted-foreground py-4">
					<p>
						Please
						<Link :href="route('login')" class="text-primary hover:underline">login</Link>
						to rent this item.
					</p>
				</div>
				<template v-else>
					<div class="space-y-2">
						<div class="font-semibold">Rental Dates</div>
						<RentalDatesPicker v-model="selectedDates" :min-date="new Date()" />
					</div>

					<Separator class="my-4" />

					<div
						v-if="selectedDates.start && selectedDates.end"
						class="text-muted-foreground space-y-2 text-sm"
					>
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
							<!-- Maybe add total here for clarity -->
							<div
								class="flex items-center justify-between font-bold mt-2 pt-2 border-t text-green-400"
							>
								<div>Total</div>
								<div>{{ formatNumber(rentalPrice.totalPrice) }}</div>
							</div>
						</div>
					</div>

					<Separator class="my-4" />

					<Button
						class="w-full"
						:disabled="
							!selectedDates.start || !selectedDates.end || rentalForm.processing
						"
						@click.prevent="handleSubmit"
					>
						{{ rentalForm.processing ? "Processing..." : "Send Rent Request" }}
					</Button>
				</template>
			</template>

			<template v-else>
				<div class="text-muted-foreground text-center py-4">
					This is your listing. Switch to My Listings to manage it.
				</div>
			</template>
		</CardContent>
	</Card>
</template>
