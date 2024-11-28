<script setup>
import {
	Breadcrumb,
	BreadcrumbItem,
	BreadcrumbLink,
	BreadcrumbList,
	BreadcrumbPage,
	BreadcrumbSeparator,
} from "@/components/ui/breadcrumb";

defineProps({ category: String, currentPage: String });
</script>

<template>
	<Breadcrumb>
		<BreadcrumbList>
			<BreadcrumbItem>
				<BreadcrumbLink :href="route('home')"> Home </BreadcrumbLink>
			</BreadcrumbItem>

			<BreadcrumbSeparator />

			<BreadcrumbItem>
				<BreadcrumbLink :href="route('explore')"> Explore </BreadcrumbLink>
			</BreadcrumbItem>

			<BreadcrumbSeparator />

			<BreadcrumbItem>
				<BreadcrumbLink :href="route('explore')"> {{ category }} </BreadcrumbLink>
			</BreadcrumbItem>

			<BreadcrumbSeparator />

			<BreadcrumbItem>
				<BreadcrumbPage>{{ currentPage }}</BreadcrumbPage>
			</BreadcrumbItem>
		</BreadcrumbList>
	</Breadcrumb>
</template>