<script setup>
import { Button } from "@/components/ui/button";

defineProps({
	CTAImage: String,
});
</script>

<template>
	<div
		:style="{
			backgroundImage: `linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url(${CTAImage})`,
			backgroundSize: 'cover',
			backgroundPosition: 'center',
			backgroundRepeat: 'no-repeat',
		}"
		class="w-full h-auto bg-cover bg-center rounded-xl grid place-items-center"
	>
		<!-- contents -->
		<div
			class="grid place-items-center text-center text-gray-100 px-7 py-10 md:py-20 md:px-14"
		>
			<!-- title -->
			<h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
				Rent to
				<span class="text-primary">Save</span>, Lend to
				<span class="text-primary">Earn</span>
			</h1>

			<!-- desc -->
			<p class="max-w-prose mt-4">
				Rent tools locally, save money, and get your projects done faster. LendWorks
				connects you with the tools you need without the hassle of ownership.
			</p>

			<!-- buttons -->
			<div class="grid grid-row-2 gap-4 w-full mt-10 sm:grid-cols-2 sm:w-80">
				<Button variant="secondary" class="w-full">List Tools</Button>
				<Button class="w-full">Rent Tools</Button>
			</div>
		</div>
	</div>
</template>
