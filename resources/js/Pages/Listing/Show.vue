<script setup>
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import ListingImages from "@/Components/ListingImages.vue";
import { Card, CardContent } from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import Separator from "@/Components/ui/separator/Separator.vue";
import { CalendarDays } from "lucide-vue-next";
import Button from "@/Components/ui/button/Button.vue";
import { format } from "date-fns";
import RentalForm from "@/Components/RentalForm.vue";

defineProps({ listing: Object, relatedListings: Object });
</script>

<template>
	<Head :title="`| Rent ${listing.title}`" />

	<Breadcrumbs
		:currentPage="listing.title"
		:category="listing.category.name"
		class="lg:flex hidden mb-10"
	/>

	<h2 class="text-2xl font-semibold tracking-tight mb-6">
		{{ listing.title }}
	</h2>

	<div class="lg:grid-cols-2 grid items-start grid-cols-1 gap-10">
		<!-- first column -->

		<div class="grid gap-6">
			<!-- images -->
			<div class="mb-10">
				<ListingImages
					:images="
						listing.images.length
							? listing.images
							: ['/storage/images/listing/default.png']
					"
				/>
			</div>

			<!-- details -->
			<div class="space-y-1">
				<h2 class="text-lg font-semibold tracking-tight">Details</h2>

				<div class="text-muted-foreground">
					{{ listing.desc }}
				</div>
			</div>

			<Separator class="my-4" />

			<!-- cancellation terms -->
			<div class="space-y-1">
				<h2 class="text-lg font-semibold tracking-tight">Cancellation Terms</h2>

				<div class="space-y-2">
					<div class="text-muted-foreground">
						<span class="font-semibold">Full Refund:</span> If the renter cancels well in
						advance (e.g., 48-72 hours before the rental period begins).
					</div>
					<div class="text-muted-foreground">
						<span class="font-semibold">Partial refund (50%):</span> If the renter cancels
						closer to the rental date (e.g., 24-48 hours before).
					</div>
					<div class="text-muted-foreground">
						<span class="font-semibold">No refund:</span> If the renter cancels last
						minute (e.g., less than 24 hours before the rental period starts) or after the
						rental period has begun.
					</div>
				</div>
			</div>

			<Separator class="my-4" />

			<!-- location -->
			<div class="space-y-1">
				<h2 class="text-lg font-semibold tracking-tight">Listing Location</h2>

				<div class="text-muted-foreground">
					<!-- {{ listing.location }} -->
					Baliwasan, ZC
				</div>
			</div>

			<Separator class="my-4" />

			<!-- user info -->
			<Card>
				<CardContent
					class="flex flex-col gap-4 p-4 text-sm sm:flex-row sm:items-center sm:justify-between"
				>
					<div class="flex items-center gap-4">
						<Avatar>
							<AvatarImage src="https://picsum.photos/200" />
							<AvatarFallback>AT</AvatarFallback>
						</Avatar>

						<div>
							<h4 class="font-semibold">Listed By {{ listing.user.name }}</h4>

							<div class="flex items-center mt-2">
								<CalendarDays class="w-4 h-4 mr-2 opacity-70" />
								<span class="text-xs text-muted-foreground">
									Joined {{ format(new Date(listing.user.created_at), "MMMM yyyy") }}
								</span>
							</div>
						</div>
					</div>

					<Link href="" class="sm:w-auto w-full">
						<Button class="w-full sm:w-auto">Message</Button>
					</Link>
				</CardContent>
			</Card>
		</div>

		<!-- second column -->
		<RentalForm :listing="listing" class="lg:min-w-96" />
	</div>

	<Separator class="my-10" />

	<!-- reviews -->
	<div class="space-y-1">
		<h2 class="text-lg font-semibold tracking-tight">Item Reviews</h2>

		<div class="text-muted-foreground"></div>
	</div>
</template>