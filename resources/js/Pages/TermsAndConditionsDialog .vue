<script setup>
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogDescription,
} from "@/components/ui/dialog";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { ref } from "vue";

const props = defineProps({
	show: {
		type: Boolean,
		required: true,
	},
});

const emit = defineEmits(["update:show", "accept"]);
const hasAccepted = ref(false);
const scrolledToBottom = ref(false);

const closeDialog = () => {
	emit("update:show", false);
};

const acceptTerms = () => {
	emit("accept", true);
	closeDialog();
};

const handleScroll = (event) => {
	const { scrollTop, scrollHeight, clientHeight } = event.target;
	// Consider scrolled to bottom when within 50px of the bottom
	if (scrollHeight - scrollTop - clientHeight < 50) {
		scrolledToBottom.value = true;
	}
};
</script>

<template>
	<Dialog :open="show" @update:open="emit('update:show', $event)">
		<DialogContent
			class="sm:max-w-3xl max-h-[90vh] p-0 overflow-hidden bg-gradient-to-b from-background to-muted/30"
		>
			<DialogHeader class="p-6 pb-2 border-b">
				<DialogTitle class="text-2xl font-bold text-primary"
					>Terms and Conditions</DialogTitle
				>
				<DialogDescription class="text-base">
					Please read our terms and conditions carefully before accepting
				</DialogDescription>
			</DialogHeader>

			<ScrollArea class="h-[70vh] p-6" @scroll="handleScroll">
				<div class="space-y-8">
					<section class="p-4 bg-card rounded-lg shadow-sm border border-border/50">
						<div class="flex items-center gap-2 mb-2">
							<div class="h-6 w-1 bg-primary rounded-full"></div>
							<h3 class="text-lg font-semibold">1. Acceptance of Terms</h3>
						</div>
						<p class="text-muted-foreground">
							By registering, browsing, or renting tools on our platform, you acknowledge
							that you have read, understood, and agreed to these Terms and Conditions. If
							you do not agree, please refrain from using our services.
						</p>
					</section>

					<section class="p-4 bg-card rounded-lg shadow-sm border border-border/50">
						<div class="flex items-center gap-2 mb-2">
							<div class="h-6 w-1 bg-primary rounded-full"></div>
							<h3 class="text-lg font-semibold">2. User Responsibilities</h3>
						</div>
						<p class="text-muted-foreground">
							You agree to provide accurate and complete information when registering and
							renting tools.
						</p>
						<p class="text-muted-foreground mt-2">
							You are responsible for the proper use, care, and timely return of rented
							tools.
						</p>
					</section>

					<section class="p-4 bg-card rounded-lg shadow-sm border border-border/50">
						<div class="flex items-center gap-2 mb-2">
							<div class="h-6 w-1 bg-primary rounded-full"></div>
							<h3 class="text-lg font-semibold">
								3. Collection and Use of Personal Information
							</h3>
						</div>
						<div class="bg-muted/50 p-3 rounded-md mb-3">
							<p class="text-sm font-medium text-primary">
								Important Privacy Information
							</p>
						</div>
						<p class="text-muted-foreground">
							To facilitate the rental process and ensure security, we collect personal
							information such as:
						</p>
						<ul class="list-disc pl-5 mt-2 text-muted-foreground">
							<li>Full name</li>
							<li>Contact details</li>
							<li>Government-issued ID (for verification purposes)</li>
						</ul>
						<p class="text-muted-foreground mt-2">Your data will be used solely for:</p>
						<ul class="list-disc pl-5 mt-2 text-muted-foreground">
							<li>Identity verification</li>
							<li>Rental agreement enforcement</li>
							<li>Payment processing</li>
							<li>Security and fraud prevention</li>
						</ul>
						<p class="text-muted-foreground mt-2">
							We are committed to safeguarding your personal information and will not
							share, sell, or misuse your data. Our platform follows strict security
							measures to protect your privacy.
						</p>
					</section>

					<section class="p-4 bg-card rounded-lg shadow-sm border border-border/50">
						<div class="flex items-center gap-2 mb-2">
							<div class="h-6 w-1 bg-primary rounded-full"></div>
							<h3 class="text-lg font-semibold">4. Tool Rental Policy</h3>
						</div>
						<p class="text-muted-foreground">All rentals are subject to availability.</p>
						<p class="text-muted-foreground mt-2">
							A deposit or security verification may be required before renting a tool.
						</p>
						<p class="text-muted-foreground mt-2">
							Rented tools must be returned in the same condition as received, within the
							agreed rental period.
						</p>
						<p class="text-muted-foreground mt-2 font-medium text-amber-600">
							Late returns may incur additional fees.
						</p>
					</section>

					<section class="p-4 bg-card rounded-lg shadow-sm border border-border/50">
						<div class="flex items-center gap-2 mb-2">
							<div class="h-6 w-1 bg-primary rounded-full"></div>
							<h3 class="text-lg font-semibold">5. Trust and Security</h3>
						</div>
						<p class="text-muted-foreground">
							We strive to provide a secure and trustworthy rental service. Users must:
						</p>
						<ul class="list-disc pl-5 mt-2 text-muted-foreground">
							<li>Trust our verification and rental processes.</li>
							<li>Treat all transactions with integrity.</li>
							<li>Report any issues or concerns immediately to our support team.</li>
						</ul>
					</section>

					<section class="p-4 bg-card rounded-lg shadow-sm border border-border/50">
						<div class="flex items-center gap-2 mb-2">
							<div class="h-6 w-1 bg-primary rounded-full"></div>
							<h3 class="text-lg font-semibold">6. Liability and Damages</h3>
						</div>
						<div class="bg-amber-50 border border-amber-200 p-3 rounded-md mb-3">
							<p class="text-sm font-medium text-amber-700">
								Important: Liability Notice
							</p>
						</div>
						<p class="text-muted-foreground">
							Users are responsible for any damage or loss of rented tools.
						</p>
						<p class="text-muted-foreground mt-2">
							We are not liable for injuries or damages caused by improper tool use.
						</p>
						<p class="text-muted-foreground mt-2">
							In cases of disputes, we reserve the right to determine the appropriate
							resolution.
						</p>
					</section>

					<section class="p-4 bg-card rounded-lg shadow-sm border border-border/50">
						<div class="flex items-center gap-2 mb-2">
							<div class="h-6 w-1 bg-primary rounded-full"></div>
							<h3 class="text-lg font-semibold">7. Amendments to Terms</h3>
						</div>
						<p class="text-muted-foreground">
							We may update these Terms and Conditions periodically. Continued use of our
							platform after any modifications constitutes acceptance of the revised
							terms.
						</p>
					</section>

					<section class="p-4 bg-card rounded-lg shadow-sm border border-border/50">
						<div class="flex items-center gap-2 mb-2">
							<div class="h-6 w-1 bg-primary rounded-full"></div>
							<h3 class="text-lg font-semibold">8. Contact Information</h3>
						</div>
						<p class="text-muted-foreground">
							For inquiries or concerns regarding these terms, please contact us at
							<a href="mailto:support@lendworks.com" class="text-primary hover:underline"
								>support@lendworks.com</a
							>.
						</p>
					</section>

					<div class="bg-muted p-4 rounded-lg border border-primary/20 my-4">
						<p class="font-medium text-center">
							By using our tool rental platform, you confirm your agreement to these Terms
							and Conditions. Happy renting!
						</p>
					</div>

					<!-- Moved the checkbox and buttons here -->
					<div class="p-6 mt-8 bg-card rounded-lg border border-border">
						<div class="flex items-start gap-3 p-4 bg-muted/50 rounded-lg border mb-4">
							<Checkbox id="terms" v-model:checked="hasAccepted" class="mt-0.5" />
							<div>
								<label for="terms" class="font-medium cursor-pointer">
									I have read and agree to the terms and conditions
								</label>
							</div>
						</div>

						<div class="flex justify-end gap-3">
							<Button variant="outline" @click="closeDialog" class="px-6">Cancel</Button>
							<Button @click="acceptTerms" :disabled="!hasAccepted" class="px-6">
								Accept Terms
							</Button>
						</div>
					</div>
				</div>
			</ScrollArea>
		</DialogContent>
	</Dialog>
</template>
