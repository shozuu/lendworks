<template>
	<footer class="bg-background -tracking-tight py-8">
		<!-- Parent div-logo -->
		<div class="container mx-auto flex flex-col items-center mt-9">
			<div class="flex items-center space-x-2 mb-1 mr-auto">
				<!-- Logo -->
				<Logo class="w-10 h-10" :fill="theme === 'dark' ? '#FFFFFF' : '#09090B'" />

				<span class="text-md font-semibold">LendWorks</span>
			</div>

			<!-- brand icons-->
			<div class="flex space-x-4 mb-2 mr-auto">
				<i class="fab fa-facebook text-md tracking-tight"></i>
				<i class="fa-brands fa-x-twitter text-md tracking-tight"></i>
				<i class="fab fa-instagram text-md tracking-tight"></i>
			</div>

			<ul class="text-[0.5rem] text-gray-500 mt-2 mr-auto">
				© 2024 LendWorks. All rights reserved.
			</ul>
		</div>

		<!-- Parent div -links -->
		<div class="flex justify-start ml-64 -mt-24">
			<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6 mt-4 sm:mt-0">
				<div>
					<h3 class="font-bold mb-2 text-xs">Products</h3>
					<ul class="space-y-1 text-[0.6rem]">
						<li><a href="#" class="hover:underline">Rent Tools</a></li>
						<li><a href="#" class="hover:underline">List Your Tools</a></li>
						<li><a href="#" class="hover:underline">Popular Listings</a></li>
						<li><a href="#" class="hover:underline">Explore Categories</a></li>
						<li><a href="#" class="hover:underline">Saved Items</a></li>
						<li><a href="#" class="hover:underline">My Rentals</a></li>
					</ul>
				</div>
				<div>
					<h3 class="font-bold mb-2 text-xs">Company</h3>
					<ul class="space-y-1 text-[0.6rem]">
						<li><a href="#" class="hover:underline">About Us</a></li>
						<li><a href="#" class="hover:underline">Blog</a></li>
						<li><a href="#" class="hover:underline">Careers</a></li>
						<li><a href="#" class="hover:underline">Newsroom</a></li>
					</ul>
				</div>
				<div>
					<h3 class="font-bold mb-2 text-xs">Support</h3>
					<ul class="space-y-1 text-[0.6rem]">
						<li><a href="#" class="hover:underline">Help Center</a></li>
						<li><a href="#" class="hover:underline">FAQs</a></li>
						<li><a href="#" class="hover:underline">How to Rent/List</a></li>
						<li><a href="#" class="hover:underline">Payment and Renting Process</a></li>
						<li><a href="#" class="hover:underline">Cancellation & Refunds</a></li>
						<li><a href="#" class="hover:underline">Contact Us</a></li>
						<li><a href="#" class="hover:underline">Report an Issue</a></li>
					</ul>
				</div>
				<div>
					<h3 class="font-bold mb-2 text-xs">Legal</h3>
					<ul class="space-y-1 text-[0.6rem]">
						<li><a href="#" class="hover:underline">Terms and Conditions</a></li>
						<li><a href="#" class="hover:underline">Privacy Policy</a></li>
						<li><a href="#" class="hover:underline">Liability Waiver</a></li>
						<li><a href="#" class="hover:underline">Safety Tips</a></li>
					</ul>
				</div>
			</div>
		</div>
	</footer>
</template>

<script>
import Logo from "../Components/Logo.vue";
import "font-awesome/css/font-awesome.min.css";
import { ref, onMounted, onUnmounted } from "vue";

export default {
	components: {
		Logo,
	},
	setup() {
		const theme = ref(document.body.getAttribute("data-theme"));

		const updateThemeStatus = () => {
			theme.value = document.body.getAttribute("data-theme");
		};

		onMounted(() => {
			const observer = new MutationObserver(updateThemeStatus);
			observer.observe(document.body, {
				attributes: true,
				attributeFilter: ["data-theme"],
			});
			onUnmounted(() => {
				observer.disconnect();
			});
		});

		return { theme };
	},
};
</script>

<style scoped>
footer {
	background-color: hsl(var(--muted));
}
</style>
