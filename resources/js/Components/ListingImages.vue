<script setup>
import { Card, CardContent } from "@/components/ui/card";
import {
	Carousel,
	CarouselContent,
	CarouselItem,
	CarouselNext,
	CarouselPrevious,
} from "@/components/ui/carousel";

defineProps({ images: Array });
</script>

<template>
	<Carousel v-slot="{ canScrollNext, canScrollPrev }" class="relative w-full">
		<CarouselContent>
			<CarouselItem v-for="(image, index) in images" :key="index">
				<div>
					<Card>
						<CardContent class="flex items-center justify-center p-0 aspect-[4/3]">
							<img
								:src="
									image.image_path
										? `/storage/${image.image_path}`
										: '/storage/images/listing/default.png'
								"
								class="object-cover object-center w-full h-full rounded-lg"
							/>
						</CardContent>
					</Card>
				</div>
			</CarouselItem>
		</CarouselContent>

		<!-- nav buttons -->
		<CarouselPrevious v-if="canScrollPrev" />
		<CarouselNext v-if="canScrollNext" />
	</Carousel>
</template>