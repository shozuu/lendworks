<script setup>
import { Badge } from "@/components/ui/badge";

defineProps({
	listing: Object,
});
</script>

<template>
	<div>
		<!-- image -->
		<Link href="" class="relative overflow-hidden">
			<img
				:src="
					listing.image
						? `/storage/${listing.image}`
						: '/storage/images/listing/default.png'
				"
				alt=""
				class="object-cover object-center w-full rounded-md aspect-square lg:aspect-auto lg:h-60"
			/>

			<!-- overlay -->
			<div
				class="absolute inset-0 rounded-md bg-gradient-to-t from-black/95 to-transparent"
			></div>

			<!-- daily rate -->
			<div class="absolute px-3 py-1 text-sm font-medium text-slate-100 bottom-2 right-2">
				₱345/day
			</div>
		</Link>

		<!-- details -->
		<div class="mt-2">
			<h3 class="text-sm font-medium line-clamp-1">
				{{ listing.title }}
			</h3>
			
            <!-- owner and location -->
			<p class="mt-3 text-xs text-muted-foreground">
				Listed By
				<Link
					:href="route('home')"
					class="hover:text-foreground underline-offset-4 hover:underline"
				>
					{{ listing.user.name }}
				</Link>

                <p class="flex items-center gap-1">Baliwasan, ZC</p>
			</p>
            
			<!-- tags -->
			<div v-if="listing.tags" class="flex items-center gap-1 mt-4">
				<div v-for="tag in listing.tags.split(',')" :key="tag">
					<Badge variant="secondary" class="font-medium">
						{{ tag }}
					</Badge>
				</div>
			</div>
		</div>
	</div>
</template>
