<script setup>
import { ref } from "vue";

const categories = ref([
	{ id: 1, name: "Power Tools", items: 50, image: "https://picsum.photos/200" },
	{ id: 2, name: "Hand Tools", items: 50, image: "https://picsum.photos/200" },
	{ id: 3, name: "Gardening Tools", items: 50, image: "https://picsum.photos/200" },
	{ id: 4, name: "Construction Tools", items: 50, image: "https://picsum.photos/200" },
	{ id: 5, name: "Electrical Tools", items: 50, image: "https://picsum.photos/200" },
	{ id: 6, name: "Plumbing Tools", items: 50, image: "https://picsum.photos/200" },
	{ id: 7, name: "Cleaning Tools", items: 50, image: "https://picsum.photos/200" },
	{ id: 8, name: "Painting Tools", items: 50, image: "https://picsum.photos/200" },
	{ id: 9, name: "Measuring Tools", items: 50, image: "https://picsum.photos/200" },
	{ id: 10, name: "Automotive Tools", items: 50, image: "https://picsum.photos/200" },
]);

const paginatedCategories = ref(categories.value.slice(0, 10));
</script>

<template>
	<slot :categories="paginatedCategories" />
</template>
