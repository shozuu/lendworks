<script setup>
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogDescription,
} from "@/components/ui/dialog";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Info, AlertTriangle } from "lucide-vue-next";

const props = defineProps({
	show: {
		type: Boolean,
		required: true,
	},
});

const emit = defineEmits(["update:show"]);

const closeDialog = () => {
	emit("update:show", false);
};
</script>

<template>
	<Dialog :open="show" @update:open="closeDialog">
		<DialogContent
			class="sm:max-w-3xl max-h-[95vh] p-0 overflow-hidden bg-gradient-to-b from-background to-muted/30"
		>
			<DialogHeader class="p-6 pb-2 border-b">
				<DialogTitle class="text-primary text-2xl font-bold">
					Terms and Conditions</DialogTitle
				>
				<DialogDescription class="text-base">
					Please review our terms and conditions carefully
				</DialogDescription>
			</DialogHeader>

			<ScrollArea class="h-[calc(95vh-100px)]">
				<div class="divide-border/40 p-6 pb-12 divide-y">
					<section class="first:pt-0 hover:bg-muted/20 py-6 transition-colors rounded-lg">
						<div class="flex items-center gap-2 mb-4">
							<div class="bg-primary w-1 h-6 rounded-full"></div>
							<h3 class="text-lg font-semibold">1. Acceptance of Terms</h3>
						</div>
						<p class="text-muted-foreground">
							By registering, browsing, or renting tools on our platform, you acknowledge
							that you have read, understood, and agreed to these Terms and Conditions. If
							you do not agree, please refrain from using our services.
						</p>
					</section>

					<section class="hover:bg-muted/20 py-6 transition-colors rounded-lg">
						<div class="flex items-center gap-2 mb-2">
							<div class="bg-primary w-1 h-6 rounded-full"></div>
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

					<section class="hover:bg-muted/20 py-6 transition-colors rounded-lg">
						<div class="flex items-center gap-2 mb-4">
							<div class="bg-primary w-1 h-6 rounded-full"></div>
							<h3 class="text-lg font-semibold">
								3. Collection and Use of Personal Information
							</h3>
						</div>
						<div class="bg-gradient-to-r from-primary/5 to-primary/10 border-primary/20 p-4 mb-4 border rounded-lg">
							<p class="text-primary flex items-center gap-2 font-medium">
									<Info class="shrink-0 w-4 h-4" />
								Important Privacy Information
							</p>
						</div>
						<p class="text-muted-foreground mb-3 font-medium">
							To facilitate the rental process and ensure security, we collect personal
							information such as:
						</p>
						<ul class="mb-4 space-y-2">
							<li class="text-muted-foreground flex items-center gap-2">
								<div class="w-1.5 h-1.5 rounded-full bg-primary/70"></div>
								Full name
							</li>
							<li class="text-muted-foreground flex items-center gap-2">
								<div class="w-1.5 h-1.5 rounded-full bg-primary/70"></div>
								Contact details
							</li>
							<li class="text-muted-foreground flex items-center gap-2">
								<div class="w-1.5 h-1.5 rounded-full bg-primary/70"></div>
								Government-issued ID (for verification purposes)
							</li>
						</ul>
						<p class="text-muted-foreground mb-3 font-medium">Your data will be used solely for:</p>
						<ul class="mb-4 space-y-2">
							<li class="text-muted-foreground flex items-center gap-2">
								<div class="w-1.5 h-1.5 rounded-full bg-primary/70"></div>
								Identity verification
							</li>
							<li class="text-muted-foreground flex items-center gap-2">
								<div class="w-1.5 h-1.5 rounded-full bg-primary/70"></div>
								Rental agreement enforcement
							</li>
							<li class="text-muted-foreground flex items-center gap-2">
								<div class="w-1.5 h-1.5 rounded-full bg-primary/70"></div>
								Payment processing
							</li>
							<li class="text-muted-foreground flex items-center gap-2">
								<div class="w-1.5 h-1.5 rounded-full bg-primary/70"></div>
								Security and fraud prevention
							</li>
						</ul>
						<p class="text-muted-foreground leading-relaxed">
							We are committed to safeguarding your personal information and will not
							share, sell, or misuse your data. Our platform follows strict security
							measures to protect your privacy.
						</p>
					</section>

					<section class="hover:bg-muted/20 py-6 transition-colors rounded-lg">
						<div class="flex items-center gap-2 mb-4">
							<div class="bg-primary w-1 h-6 rounded-full"></div>
							<h3 class="text-lg font-semibold">4. Tool Rental Policy</h3>
						</div>
						<div class="space-y-3">
							<p class="text-muted-foreground">All rentals are subject to availability.</p>
							<p class="text-muted-foreground">
								A deposit or security verification may be required before renting a tool.
							</p>
							<p class="text-muted-foreground">
								Rented tools must be returned in the same condition as received, within the
								agreed rental period.
							</p>
							<div class="bg-amber-50/50 border-amber-200/70 p-3 mt-3 border rounded-lg">
								<p class="text-amber-700 flex items-center gap-2 font-medium">
										<AlertTriangle class="shrink-0 w-4 h-4" />
									Late returns may incur additional fees.
								</p>
							</div>
						</div>
					</section>

					<section class="hover:bg-muted/20 py-6 transition-colors rounded-lg">
						<div class="flex items-center gap-2 mb-2">
							<div class="bg-primary w-1 h-6 rounded-full"></div>
							<h3 class="text-lg font-semibold">5. Trust and Security</h3>
						</div>
						<p class="text-muted-foreground">
							We strive to provide a secure and trustworthy rental service. Users must:
						</p>
						<ul class="text-muted-foreground pl-5 mt-2 list-disc">
							<li>Trust our verification and rental processes.</li>
							<li>Treat all transactions with integrity.</li>
							<li>Report any issues or concerns immediately to our support team.</li>
						</ul>
					</section>

					<section class="hover:bg-muted/20 py-6 transition-colors rounded-lg">
						<div class="flex items-center gap-2 mb-2">
							<div class="bg-primary w-1 h-6 rounded-full"></div>
							<h3 class="text-lg font-semibold">6. Liability and Damages</h3>
						</div>
						<div class="bg-amber-50 border-amber-200 p-3 mb-3 border rounded-md">
							<p class="text-amber-700 font-medium">
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

					<section class="hover:bg-muted/20 py-6 transition-colors rounded-lg">
						<div class="flex items-center gap-2 mb-2">
							<div class="bg-primary w-1 h-6 rounded-full"></div>
							<h3 class="text-lg font-semibold">7. Amendments to Terms</h3>
						</div>
						<p class="text-muted-foreground">
							We may update these Terms and Conditions periodically. Continued use of our
							platform after any modifications constitutes acceptance of the revised
							terms.
						</p>
					</section>

					<section class="hover:bg-muted/20 py-6 transition-colors rounded-lg">
						<div class="flex items-center gap-2 mb-2">
							<div class="bg-primary w-1 h-6 rounded-full"></div>
							<h3 class="text-lg font-semibold">8. Contact Information</h3>
						</div>
						<p class="text-muted-foreground">
							For inquiries or concerns regarding these terms, please contact us at
							<a href="mailto:support@lendworks.com" class="text-primary hover:underline"
								>support@lendworks.com</a
							>.
						</p>
					</section>
				</div>
			</ScrollArea>
		</DialogContent>
	</Dialog>
</template>

