<script setup>
import { router } from "@inertiajs/vue3";
import { formatNumber } from "@/lib/formatters";

// use route.params() to 'stack' search query parameters coming from different components and pass them as one parameter
const params = route().params;

defineProps({
	listing: Object,
});

const selectUser = (id) => {
	router.get(route('home'), {
		// this should redirect to a dedicated profile page for that user
		user_id: id,
	})
}
</script>

<template>
	<Link :href="route('listing.show', listing.id)">
		<!-- image -->
		<div class="relative overflow-hidden">
			<img
				:src="
					listing.images.length
						? `/storage/${listing.images[0].image_path}`
						: '/storage/images/listing/default.png'
				"
				alt=""
				class="object-cover object-center w-full rounded-md aspect-[4/3] lg:aspect-auto lg:h-60"
			/>

			<!-- overlay -->
			<div
				class="bg-gradient-to-t from-black/95 to-transparent absolute inset-0 rounded-md"
			></div>

			<!-- daily rate -->
			<div class="text-slate-100 bottom-2 right-2 absolute px-3 py-1 text-sm font-medium">
				{{ formatNumber(listing.price) }}/day
			</div>
		</div>

		<!-- details -->
		<div class="mt-2 space-y-2">
			<h3 class="line-clamp-1 text-sm font-medium">
				{{ listing.title }}
			</h3>

            <!-- owner and location -->
			<p class="text-muted-foreground text-xs">
				Listed By
				<Link
					v-if="listing.user"
					@click="selectUser(listing.user.id)"
					:href="route('home')"
					class="hover:text-foreground underline-offset-4 hover:underline"
				>
					{{ listing.user.name }}
				</Link>

                <p class="flex items-center gap-1">Baliwasan, ZC</p>
			</p>
		</div>
	</Link>
</template>
