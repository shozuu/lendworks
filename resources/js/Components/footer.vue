<script setup>
import Logo from "./Logo.vue";
import { Link } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";
import { Facebook, Twitter, Instagram, Linkedin } from "lucide-vue-next";

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
</script>

<template>
	<footer class="bg-gradient-to-b from-footer to-background mt-12 border-t">
		<div class="max-w-screen-2xl sm:px-6 px-4 pt-16 mx-auto">
			<div class="grid grid-cols-1 lg:grid-cols-[400px_1fr] gap-12">
				<!-- logo description social -->
				<div class="space-y-8">
					<Link
						:href="route('home')"
						class="hover:opacity-80 inline-block transition-opacity"
					>
						<Logo class="w-12 h-12" :fill="theme === 'dark' ? '#FFFFFF' : '#09090B'" />
					</Link>
					<p class="text-muted-foreground text-sm leading-relaxed">
						Rent tools locally, save money, and get your projects done faster. Join our
						community of DIY enthusiasts. Experience hassle-free tool rental with our
						secure platform.
					</p>
					<div class="flex items-center space-x-4">
						<a
							href="#"
							class="hover:text-primary text-muted-foreground p-2 transition-colors"
						>
							<span class="sr-only">Facebook</span>
							<Facebook class="w-5 h-5" />
						</a>
						<a
							href="#"
							class="hover:text-primary text-muted-foreground p-2 transition-colors"
						>
							<span class="sr-only">Twitter</span>
							<Twitter class="w-5 h-5" />
						</a>
						<a
							href="#"
							class="hover:text-primary text-muted-foreground p-2 transition-colors"
						>
							<span class="sr-only">Instagram</span>
							<Instagram class="w-5 h-5" />
						</a>
						<a
							href="#"
							class="hover:text-primary text-muted-foreground p-2 transition-colors"
						>
							<span class="sr-only">LinkedIn</span>
							<Linkedin class="w-5 h-5" />
						</a>
					</div>
				</div>

				<!-- nav links -->
				<div class="md:grid-cols-4 grid grid-cols-2 gap-8">
					<!-- about -->
					<div class="space-y-4">
						<h3 class="text-foreground text-sm font-semibold tracking-wide uppercase">
							About
						</h3>
						<ul class="space-y-3 text-sm">
							<li>
								<Link
									href="#"
									class="text-muted-foreground hover:text-primary transition-colors"
									>Our Story</Link
								>
							</li>
							<li>
								<Link
									href="#"
									class="text-muted-foreground hover:text-primary transition-colors"
									>Careers</Link
								>
							</li>
							<li>
								<Link
									href="#"
									class="text-muted-foreground hover:text-primary transition-colors"
									>Press</Link
								>
							</li>
						</ul>
					</div>

					<!-- quick links -->
					<div class="space-y-4">
						<h3 class="text-foreground text-sm font-semibold tracking-wide uppercase">
							Quick Links
						</h3>
						<ul class="space-y-3 text-sm">
							<li>
								<Link
									:href="route('explore')"
									class="text-muted-foreground hover:text-primary transition-colors"
									>Explore</Link
								>
							</li>
							<li>
								<Link
									:href="route('listing.create')"
									class="text-muted-foreground hover:text-primary transition-colors"
									>List a Tool</Link
								>
							</li>
							<li>
								<Link
									:href="route('my-rentals')"
									class="text-muted-foreground hover:text-primary transition-colors"
									>My Rentals</Link
								>
							</li>
						</ul>
					</div>

					<!-- support -->
					<div class="space-y-4">
						<h3 class="text-foreground text-sm font-semibold tracking-wide uppercase">
							Support
						</h3>
						<ul class="space-y-3 text-sm">
							<li>
								<Link
									href="#"
									class="text-muted-foreground hover:text-primary transition-colors"
									>Help Center</Link
								>
							</li>
							<li>
								<Link
									href="#"
									class="text-muted-foreground hover:text-primary transition-colors"
									>Safety Center</Link
								>
							</li>
							<li>
								<Link
									href="#"
									class="text-muted-foreground hover:text-primary transition-colors"
									>Contact Us</Link
								>
							</li>
						</ul>
					</div>

					<!-- legal -->
					<div class="space-y-4">
						<h3 class="text-foreground text-sm font-semibold tracking-wide uppercase">
							Legal
						</h3>
						<ul class="space-y-3 text-sm">
							<li>
								<Link
									href="#"
									class="text-muted-foreground hover:text-primary transition-colors"
									>Terms of Service</Link
								>
							</li>
							<li>
								<Link
									href="#"
									class="text-muted-foreground hover:text-primary transition-colors"
									>Privacy Policy</Link
								>
							</li>
							<li>
								<Link
									href="#"
									class="text-muted-foreground hover:text-primary transition-colors"
									>Cookie Policy</Link
								>
							</li>
						</ul>
					</div>
				</div>
			</div>

			<!-- copyright -->
			<div class="border-border pt-8 mt-16 mb-8 border-t">
				<p class="text-muted-foreground text-xs text-center">
					&copy; {{ new Date().getFullYear() }} LendWorks. All rights reserved.
				</p>
			</div>
		</div>
	</footer>
</template>
