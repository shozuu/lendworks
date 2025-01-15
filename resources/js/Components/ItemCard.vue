<script setup>
import { router } from "@inertiajs/vue3";
import { formatNumber } from "@/lib/formatters";

defineProps({
	listing: {
		type: Object,
		required: true,
	},
});

const selectUser = (event, id) => {
	event.preventDefault(); // Prevent parent link from triggering
	router.get(route("home"), { user_id: id });
};

const getFirstName = (fullName) => {
	return fullName.split(" ")[0];
};
</script>

<template>
	<Link :href="route('listing.show', listing.id)" class="block group">
		<!-- image -->
		<div class="relative overflow-hidden rounded-md aspect-[4/3]">
			<img
				:src="
					listing.images.length
						? `/storage/${listing.images[0].image_path}`
						: '/storage/images/listing/default.png'
				"
				:alt="listing.title"
				class="object-cover w-full h-full transition-transform group-hover:scale-105"
			/>

			<!-- overlay -->
			<div
				class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"
			></div>

			<!-- location and price -->
			<div class="absolute bottom-0 left-0 right-0 p-2 flex justify-between items-center">
				<div v-if="listing.location" class="text-white text-xs truncate pr-2">
					{{ listing.location.province }}, {{ listing.location.city }}
				</div>
				<div class="text-white text-sm font-medium shrink-0">
					{{ formatNumber(listing.price) }}/day
				</div>
			</div>
		</div>

		<!-- title and owner -->
		<div class="mt-2 space-y-0.5">
			<h3 class="line-clamp-1 font-medium">{{ listing.title }}</h3>

			<div class="flex items-center gap-1 text-xs text-muted-foreground mt-2">
				<button
					v-if="listing.user"
					@click="(e) => selectUser(e, listing.user.id)"
					class="font-medium hover:text-foreground hover:underline underline-offset-4"
				>
					Listed By: {{ getFirstName(listing.user.name) }}
				</button>
			</div>
		</div>
	</Link>
</template>
