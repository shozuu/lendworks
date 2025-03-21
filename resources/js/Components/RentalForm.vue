<script setup>
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import Separator from "@/Components/ui/separator/Separator.vue";
import Button from "@/Components/ui/button/Button.vue";
import RentalDatesPicker from "./RentalDatesPicker.vue";
import { calculateRentalPrice } from "@/lib/rentalCalculator";
import { formatNumber, formatDate } from "@/lib/formatters";
import { ref, reactive, watch, onMounted, computed } from "vue";
import { useForm as useInertiaForm } from "@inertiajs/vue3";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { XCircle, Clock } from "lucide-vue-next";
import TermsAndConditionsDialog from "../Pages/TermsAndConditionsDialog .vue";
import { Checkbox } from "@/Components/ui/checkbox"; 

const props = defineProps({
	listing: {
		type: Object,
		required: true,
	},
	isOwner: {
		type: Boolean,
		default: false,
	},
	flashError: {
		type: String,
		default: null,
	},
	currentRental: {
		type: Object,
		default: null,
	},
});
const showTerms = ref(false);
const termsAccepted = ref(false);
const dailyRate = props.listing.price;
const itemValue = props.listing.value;
const rentalDays = ref(7);
const quantity = ref(1);

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
	deposit_fee: props.listing.deposit_fee,
	total_price: 0,
	quantity_requested: 1,
});

const selectedDates = ref({
	start: null,
	end: null,
});

const errors = ref({});
const isSubmitting = ref(false);

// Add computed property for available quantity
const availableQuantity = computed(() => props.listing.available_quantity);

function updateRentalPrice() {
	const result = calculateRentalPrice(
		dailyRate,
		itemValue,
		rentalDays.value,
		props.listing.deposit_fee,
		quantity.value
	);
	Object.assign(rentalPrice, result);

	// Update form values
	rentalForm.base_price = result.basePrice;
	rentalForm.discount = result.discount;
	rentalForm.service_fee = result.fee;
	rentalForm.deposit_fee = result.deposit;
	rentalForm.total_price = result.totalPrice;
	rentalForm.quantity_requested = quantity.value;
}

function updateRentalDays(startDate, endDate) {
	const start = new Date(startDate);
	const end = new Date(endDate);

	// Set time to midnight to avoid time zone issues
	start.setHours(0, 0, 0, 0);
	end.setHours(0, 0, 0, 0);

	const diffTime = Math.abs(end.getTime() - start.getTime());
	// Add 1 to include both start and end dates in the count
	rentalDays.value = Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;

	updateRentalPrice();
}

watch(rentalDays, (newVal) => {
	updateRentalPrice();
});

watch(
	selectedDates,
	(newVal) => {
		if (newVal.start && newVal.end) {
			// Format dates in YYYY-MM-DD format for the Manila timezone
			const manila = new Intl.DateTimeFormat("en-CA", {
				year: "numeric",
				month: "2-digit",
				day: "2-digit",
				timeZone: "Asia/Manila",
			});

			rentalForm.start_date = manila.format(newVal.start);
			rentalForm.end_date = manila.format(newVal.end);

			updateRentalDays(newVal.start, newVal.end);
			rentalForm.base_price = rentalPrice.basePrice;
			rentalForm.discount = rentalPrice.discount;
			rentalForm.service_fee = rentalPrice.fee;
			rentalForm.total_price = rentalPrice.totalPrice;
		}
	},
	{ deep: true }
);

// Add watch for quantity changes
watch(quantity, (newVal) => {
	if (newVal > availableQuantity.value) {
		quantity.value = availableQuantity.value;
	}
	updateRentalPrice();
});

const handleSubmit = () => {
	if (!rentalForm.start_date || !rentalForm.end_date) {
		errors.value = {
			dates: ["Please select start and end dates"],
		};
		return;
	}

	rentalForm.post(route("rentals.store"), {
		onStart: () => {
			isSubmitting.value = true;
			errors.value = {};
		},
		onSuccess: () => {
			isSubmitting.value = false;
		},
		onError: (err) => {
			errors.value = err;
			isSubmitting.value = false;
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

			<!-- Currently Rented Notice -->
			<div v-if="listing.is_rented && currentRental" class="mb-6">
				<Alert class="bg-muted">
					<AlertDescription>
						<div class="flex items-center gap-2">
							<Clock class="shrink-0 w-4 h-4" />
							<p class="font-medium">Currently Rented</p>
						</div>
						<p class="text-muted-foreground mt-1 text-sm">
							This item is being rented until
							{{ currentRental?.end_date ? formatDate(currentRental.end_date) : "N/A" }}.
						</p>
						<p class="text-muted-foreground mt-2 text-sm">
							You can still calculate rental costs below, but rental requests are
							temporarily disabled.
							<!-- Future feature hint -->
							<!-- <button class="text-primary hover:underline">Get notified when available</button> -->
						</p>
					</AlertDescription>
				</Alert>
			</div>

			<!-- Rental error alert -->
			<Alert v-if="flashError" variant="destructive" class="flex items-center mb-4">
				<XCircle class="shrink-0 w-4 h-4" />
				<AlertDescription>
					{{ flashError }}
				</AlertDescription>
			</Alert>

			<div class="mb-4">
				<h3 class="text-base font-semibold">Sample Rental Prices</h3>
				<div class="text-muted-foreground mt-1 text-xs">
					Prices shown include platform fees. Security deposit may apply separately.
				</div>
			</div>

			<div class="md:grid-cols-3 grid grid-cols-1 gap-4 text-sm">
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

			<!-- Owner check should be at top level inside CardContent -->
			<div v-if="isOwner" class="text-muted-foreground py-4 text-center">
				This is your listing. Switch to My Listings to manage it.
			</div>
			<template v-else>
				<div v-if="!$page.props.auth.user" class="text-muted-foreground py-4 text-center">
					<p>
						Please
						<Link :href="route('login')" class="text-primary hover:underline">login</Link>
						to rent this item.
					</p>
				</div>
				<template v-else>
					<!-- Update quantity section -->
					<div class="space-y-2">
						<div class="font-semibold">Quantity</div>
						<div class="flex items-center gap-2">
							<Button
								variant="outline"
								size="sm"
								:disabled="quantity === 1"
								@click="quantity--"
							>
								-
							</Button>
							<span class="w-12 text-center">{{ quantity }}</span>
							<Button
								variant="outline"
								size="sm"
								:disabled="quantity >= availableQuantity"
								@click="quantity++"
							>
								+
							</Button>
						</div>
						<p class="text-muted-foreground text-xs">
							{{ availableQuantity }} unit{{ availableQuantity !== 1 ? "s" : "" }}
							available for rent
						</p>
					</div>

					<div class="space-y-2">
						<div class="font-semibold">Rental Dates</div>
						<RentalDatesPicker v-model="selectedDates" :min-date="new Date()" />
					</div>

					<Separator class="my-4" v-if="selectedDates.start && selectedDates.end" />

					<div v-if="selectedDates.start && selectedDates.end" class="space-y-2 text-sm">
						<div class="text-foreground text-base font-semibold">Rental Summary</div>

						<!-- breakdown -->
						<div class="space-y-2">
							<div class="flex items-center justify-between">
								<div class="text-muted-foreground">
									{{ formatNumber(dailyRate) }} × {{ rentalDays }} days ×
									{{ quantity }} unit(s)
								</div>
								<div>{{ formatNumber(rentalPrice.basePrice) }}</div>
							</div>

							<div class="flex items-center justify-between">
								<div class="text-muted-foreground">
									Duration Discount ({{ rentalPrice.discountPercentage }}%)
								</div>
								<div class="text-emerald-500 font-medium">
									- {{ formatNumber(rentalPrice.discount) }}
								</div>
							</div>

							<div class="flex items-center justify-between">
								<div class="text-muted-foreground">LendWorks Fee</div>
								{{ formatNumber(rentalPrice.fee) }}
							</div>

							<div class="flex items-center justify-between">
								<span class="text-muted-foreground">Security Deposit (Refundable)</span>
								<div class="text-primary">
									{{ formatNumber(rentalForm.deposit_fee) }}
								</div>
							</div>

							<Separator class="my-2" />

							<!-- total with deposit -->
							<div class="flex justify-between text-lg font-bold">
								<div>Total Due</div>
								<div class="text-primary">
									{{ formatNumber(rentalForm.total_price) }}
								</div>
							</div>

							<p class="text-muted-foreground mt-2 text-xs">
								Note: Security deposit will be refunded after the rental period, subject
								to item condition
							</p>
						</div>
					</div>

					<Separator class="my-4" />

					<!-- Add error display -->
					<div
						v-if="Object.keys(errors).length"
						class="text-destructive my-2 text-sm text-center"
					>
						<p v-for="error in errors" :key="error">{{ error }}</p>
					</div>

					<div class="flex items-start gap-2 mt-4 mb-4" v-if="selectedDates.start && selectedDates.end">
						<Checkbox 
							id="rental-terms"
							v-model:checked="termsAccepted" 
							class="mt-1"
						/>
						<label for="rental-terms" class="text-sm cursor-pointer">
							By renting, I have read and agree to the 
							<button
								type="button"
								@click="showTerms = true"
								class="text-primary hover:underline inline-flex font-medium"
							>
								Terms and Conditions
							</button>
						</label>
					</div>

					<Button
						class="w-full"
						:disabled="
							!selectedDates.start ||
							!selectedDates.end ||
							isSubmitting ||
							listing.is_rented ||
							!termsAccepted
						"
						@click.prevent="handleSubmit"
					>
						<template v-if="isSubmitting">
							<span class="loading-spinner mr-2"></span> Processing...
						</template>
						<template v-else-if="listing.is_rented">
							Available on {{ formatDate(currentRental.end_date) }}
						</template>
						<template v-else> Send Rent Request </template>
					</Button>

					<TermsAndConditionsDialog
						v-model:show="showTerms"
						@accept="termsAccepted = true"
					/>
				</template>
			</template>
		</CardContent>
	</Card>
</template>
