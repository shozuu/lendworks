<script setup>
import { Card } from "@/components/ui/card";
import { formatNumber, timeAgo } from "@/lib/formatters";
import { Tags, MapPin, PhilippinePeso, Clock } from "lucide-vue-next";
import ListingStatusBadge from "@/Components/ListingStatusBadge.vue";

const props = defineProps({
	listing: {
		type: Object,
		required: true,
	},
});
</script>

<template>
	<Card>
		<!-- Banner Slot -->
		<slot name="banner" />

		<div class="flex flex-col sm:flex-row gap-4 p-4">
			<!-- Thumbnail -->
			<div class="sm:w-32 sm:h-32 w-24 h-24 overflow-hidden rounded-md shrink-0">
				<Link
					:href="
						route(
							$page.props.auth?.user?.is_admin ? 'admin.listings.show' : 'listing.show',
							listing.id
						)
					"
					class="h-full"
				>
					<img
						:src="
							listing.images[0]?.image_path
								? `/storage/${listing.images[0].image_path}`
								: '/storage/images/listing/default.png'
						"
						:alt="listing.title"
						class="object-cover w-full h-full"
					/>
				</Link>
			</div>

			<!-- Content -->
			<div class="flex-1 flex flex-col gap-3">
				<!-- Title and Status -->
				<div class="flex items-start flex-col sm:flex-row justify-between gap-1">
					<Link
						:href="
							route(
								$page.props.auth?.user?.is_admin ? 'admin.listings.show' : 'listing.show',
								listing.id
							)
						"
						class="hover:underline font-semibold text-sm sm:text-base line-clamp-1"
					>
						{{ listing.title }}
					</Link>
					<ListingStatusBadge
						:status="listing.status"
						:is-available="listing.is_available"
					/>
				</div>

				<!-- Details -->
				<div class="text-muted-foreground text-xs sm:text-sm space-y-1.5 flex-1">
					<div class="flex items-center gap-1">
						<Tags class="w-4 h-4 shrink-0" />
						<span class="truncate">{{ listing.category?.name }}</span>
					</div>

					<div class="flex items-center gap-1">
						<PhilippinePeso class="w-4 h-4 shrink-0" />
						<span class="whitespace-nowrap">{{ formatNumber(listing.price) }}/day</span>
					</div>

					<div class="flex items-center gap-1">
						<MapPin class="w-4 h-4 shrink-0" />
						<span class="line-clamp-1">
							{{ listing.location?.address ?? "No location specified" }}
						</span>
					</div>

					<div class="flex items-center gap-1">
						<Clock class="w-4 h-4 shrink-0" />
						<span>Listed {{ timeAgo(listing.created_at) }}</span>
					</div>

					<!-- Extra Details Slot -->
					<slot name="extra-details" />
				</div>

				<!-- Actions Slot -->
				<slot name="actions" />
			</div>
		</div>
	</Card>
</template>
