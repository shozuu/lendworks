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

defineProps({ listing: Object });
const formatNumber = (value) => {
	return new Intl.NumberFormat("en-US").format(value);
};
</script>

<template>
	<Card>
		<CardHeader class="md:p-6 p-4 pt-6">
			<CardTitle> ₱{{ formatNumber(listing.price) }} per day</CardTitle>
			<CardDescription>₱{{ formatNumber(2350.0) }} for 7 days</CardDescription>
		</CardHeader>

		<CardContent class="md:p-6 md:pt-0 p-4 pt-0">
			<Separator class="my-4" />

			<div class="space-y-2">
				<div class="font-semibold">Rental Dates</div>
				<RentalDatesPicker />
			</div>

			<Separator class="my-4" />

			<div class="text-muted-foreground space-y-2 text-sm">
				<div class="text-foreground text-base font-semibold">Rental Summary</div>

				<div class="space-y-1">
					<div class="flex items-center justify-between">
						<div>₱400 x 7 rental days</div>
						<div>₱2,800</div>
					</div>
					<div class="flex items-center justify-between">
						<div>Duration Discount (10%)</div>
						<div>- ₱280</div>
					</div>
					<div class="flex items-center justify-between">
						<div>LendWorks Fee</div>
						<div>₱630</div>
					</div>
				</div>
			</div>

			<Separator class="my-4" />

			<div class="flex items-center justify-between font-semibold text-green-500">
				<div>Total</div>
				<div>₱2,800</div>
			</div>
		</CardContent>

		<CardFooter>
			<Link href="" class="w-full">
				<Button class="w-full"> Send Rent Request </Button>
			</Link>
		</CardFooter>
	</Card>
</template>