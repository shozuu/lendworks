<script setup>
import {
	WrenchIcon,
	Hammer,
	HardHat,
	Shovel,
	Car,
	PaintBucket,
	Sprout,
	Ruler,
	Wrench,
	Briefcase,
	Shield,
	Boxes,
} from "lucide-vue-next";

const props = defineProps({
	category: Object,
});

const getCategoryIcon = (name) => {
	// Map category names to specific icons
	const categoryIcons = {
		"Power Tools": WrenchIcon,
		"Hand Tools": Hammer,
		"Construction Equipment": HardHat,
		"Gardening Tools and Equipment": Shovel,
		"Automotive Tools": Car,
		"Painting & Decorating": PaintBucket,
		"Cleaning Equipment": Sprout,
		"Measuring Tools": Ruler,
		"DIY & Craft Tools": Wrench,
		"Electrical & Plumbing Equipment": Briefcase,
		"Safety Gear": Shield,
		"Specialty Tools": Boxes,
	};

	// Return the mapped icon or Boxes as default
	return categoryIcons[name] || Boxes;
};
</script>

<template>
	<Link
		:href="route('explore', { category: category.id })"
		class="hover:border-primary flex items-center p-3 py-4 space-x-3 transition-all border rounded-lg"
	>
		<component
			:is="getCategoryIcon(category.name)"
			class="w-5 h-5 text-primary shrink-0"
		/>
		<div class="flex flex-col">
			<p class="line-clamp-1 text-sm font-semibold">{{ category.name }}</p>
			<p class="text-foreground line-clamp-1 text-xs">{{ category.description }}</p>
		</div>
	</Link>
</template>
