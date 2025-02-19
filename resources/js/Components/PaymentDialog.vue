<script setup>
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { ScrollArea } from "@/components/ui/scroll-area";
import { formatNumber } from "@/lib/formatters";
import { useForm as useVeeForm } from "vee-validate";
import { useForm as useInertiaForm } from "@inertiajs/vue3";
import { computed } from "vue";
import { toTypedSchema } from "@vee-validate/zod";
import { vAutoAnimate } from "@formkit/auto-animate/vue";
import * as z from "zod";
import {
	Form,
	FormControl,
	FormField,
	FormItem,
	FormLabel,
	FormMessage,
} from "@/components/ui/form";
import ImageUpload from "@/Components/ImageUpload.vue";
import RentalStatusBadge from "@/Components/RentalStatusBadge.vue";

const props = defineProps({
	show: Boolean,
	rental: Object,
	payment: Object,
	historical: {
		type: Boolean,
		default: false
	},
	viewOnly: {
		type: Boolean,
		default: false
	}
});

const emit = defineEmits(["update:show"]);

const qrCodePath = "/storage/images/payment/QR_Payment.jpg";

const formSchema = toTypedSchema(
	z.object({
		reference_number: z
			.string()
			.min(1, "Reference number is required")
			.max(50, "Reference number is too long"),
		payment_proof: z.preprocess(
			(value) => {
				if (Array.isArray(value) && value.length > 0) {
					const notFileInstance = value.every((item) => !(item instanceof File));
					if (notFileInstance) {
						value = value.map((item) => item.file);
					}
				}
				return value;
			},
			z
				.array(
					z
						.instanceof(File)
						.refine((file) => file.size <= 3 * 1024 * 1024, {
							message: "Image must be less than 3MB",
						})
						.refine(
							(file) =>
								["image/jpeg", "image/png", "image/jpg", "image/webp"].includes(
									file.type
								),
							{
								message: "Only JPEG, PNG, JPG, and WEBP images are allowed.",
							}
						)
				)
				.length(1, { message: "Payment proof screenshot is required" })
		),
	})
);

const form = useVeeForm({
	validationSchema: formSchema,
});

const inertiaForm = useInertiaForm({
	reference_number: "",
	payment_proof: null,
});

const onSubmit = form.handleSubmit((values) => {
	inertiaForm.reference_number = values.reference_number;
	inertiaForm.payment_proof = values.payment_proof[0];

	inertiaForm.post(route("rentals.submit-payment", props.rental.id), {
		preserveScroll: true,
		onSuccess: () => {
			emit("update:show", false);
		},
	});
});

const getPaymentProofUrl = (path) => {
	if (!path) return null;
	// Remove any leading slashes to prevent double slashes
	return `/storage/${path.replace(/^\//, "")}`;
};

// Add computed property for submission date
const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

// Add computed for admin route check
const isAdminRoute = computed(() => {
    return window.location.pathname.startsWith('/admin');
});

// Add computed for rental transaction route
const transactionRoute = computed(() => {
    if (!props.rental) return null;
    return isAdminRoute.value
        ? route('admin.rental-transactions.show', props.rental.id)
        : route('rental.show', props.rental.id);
});
</script>

<template>
	<Dialog :open="show" @update:open="$emit('update:show', $event)">
		<DialogContent class="sm:max-w-md flex flex-col max-h-[90vh]">
			<DialogHeader>
				<DialogTitle>
					 {{ viewOnly ? 'Payment Details' : historical ? 'Historical Payment Details' : 'Payment Details' }}
				</DialogTitle>
				<DialogDescription>
					{{
						viewOnly 
							? "Review payment details below"
							: historical 
								? 'View payment details as they were at this point in time'
								: payment
									? "Review your payment details below"
									: "Choose your preferred payment method below"
					}}
				</DialogDescription>
			</DialogHeader>

			<ScrollArea class="flex-1 pr-2 overflow-y-auto">
				<!-- Show existing payment if it exists and we're in view/historical mode -->
				<div v-if="payment && (viewOnly || historical)" class="space-y-6">
					 <!-- Rental Context Section - Add this section -->
                    <div class="bg-muted p-4 space-y-3 rounded-lg">
                        <div class="flex items-start gap-4">
                            <!-- Listing Image -->
                            <img 
                                :src="rental.listing.images[0]?.image_path 
                                    ? `/storage/${rental.listing.images[0].image_path}`
                                    : '/storage/images/listing/default.png'"
                                class="object-cover w-20 h-20 rounded-md"
                                :alt="rental.listing.title"
                            />
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium truncate">
                                    {{ rental?.listing?.title }}
                                </h4>
                                <div class="text-muted-foreground space-y-1 text-sm">
                                    <p><span class="font-medium">Lender:</span> {{ rental?.listing?.user?.name }}</p>
                                    <p><span class="font-medium">Renter:</span> {{ rental?.renter?.name }}</p>
                                </div>
                            </div>
                        </div>
                        <Link 
                            v-if="transactionRoute"
                            :href="transactionRoute"
                            class="text-primary hover:underline inline-flex items-center gap-1 text-sm"
                        >
                            View Rental Transaction Details
                            <ChevronRight class="w-4 h-4" />
                        </Link>
                    </div>

					<!-- Payment Summary with Status Badge -->
					<div class="bg-muted p-4 space-y-4 rounded-lg">
						<div class="flex items-center justify-between">
							<span class="text-sm font-medium">Amount Paid:</span>
							<span class="text-lg font-medium">{{
								formatNumber(payment.total_price || rental.total_price)
							}}</span>
						</div>
						<div class="flex items-center justify-between">
							<span class="text-muted-foreground text-sm">Status:</span>
							<RentalStatusBadge :status="payment.status" />
						</div>
						<div class="text-muted-foreground pt-3 space-y-1 text-sm border-t">
							<div class="flex justify-between">
								<span>Submitted:</span>
								<span>{{ formatDate(payment.created_at) }}</span>
							</div>
							<div v-if="payment.verified_at" class="flex justify-between">
								<span>Verified:</span>
								<span>{{ formatDate(payment.verified_at) }}</span>
							</div>
						</div>
					</div>

					<!-- Admin Feedback if exists -->
					<Alert v-if="payment.admin_feedback" variant="destructive">
						<AlertDescription class="text-sm italic">
							"{{ payment.admin_feedback }}"
						</AlertDescription>
					</Alert>

					<!-- Payment Details -->
					<div class="space-y-4">
						<!-- Reference Details -->
						<div class="space-y-3">
							<h3 class="text-sm font-medium">Payment Information</h3>
							<div class="bg-muted/50 p-3 space-y-2 text-sm rounded-md">
								<div class="flex justify-between">
									<span class="text-muted-foreground">Method:</span>
									<span>GCash</span>
								</div>
								<div class="flex justify-between">
									<span class="text-muted-foreground">Reference Number:</span>
									<span class="font-medium">{{ payment.reference_number }}</span>
								</div>
							</div>
						</div>

						<!-- Payment Screenshot -->
						<div v-if="payment?.payment_proof_path" class="space-y-3">
							<h3 class="text-sm font-medium">Payment Screenshot</h3>
							<div class="bg-muted/50 overflow-hidden rounded-lg">
								<img
									:src="getPaymentProofUrl(payment.payment_proof_path)"
									alt="Payment Proof"
									class="object-contain w-full h-auto"
									@error="(e) => (e.target.src = '/images/placeholder.png')"
								/>
							</div>
						</div>
					</div>
				</div>

				 <!-- Show payment form if no payment exists or we're not in view/historical mode -->
				<div v-else-if="!viewOnly && (!payment || !historical)" class="space-y-6">
					<!-- Payment Amount -->
					<div class="bg-muted p-4 rounded-lg">
						<div class="flex items-center justify-between font-medium">
							<span class="text-sm">Amount to Pay:</span>
							<span class="text-lg">{{ formatNumber(rental.total_price) }}</span>
						</div>
					</div>

					<!-- Payment Methods -->
					<div class="space-y-6 divide-y">
						<!-- QR Code Method -->
						<div class="space-y-4">
							<h3 class="font-medium">Scan QR Code</h3>
							<div class="flex justify-center">
								<img
									:src="qrCodePath"
									alt="Payment QR Code"
									class="object-contain w-48 h-48 border rounded-lg"
								/>
							</div>
						</div>

						<!-- Manual Input Method -->
						<div class="pt-6 space-y-4">
							<div class="space-y-2">
								<h3 class="font-medium">GCash Details</h3>
								<div class="bg-muted/50 p-3 space-y-1 rounded-md">
									<p class="text-sm">Name: AL**N BE****D T.</p>
									<p class="text-sm">Number: 09359447500</p>
								</div>
							</div>

							<!-- Payment Form -->
							<form @submit="onSubmit" class="space-y-4">
								<FormField v-slot="{ componentField }" name="reference_number">
									<FormItem v-auto-animate>
										<FormLabel>GCash Reference Number</FormLabel>
										<FormControl>
											<Input
												type="text"
												placeholder="Enter your GCash reference number"
												v-bind="componentField"
											/>
										</FormControl>
										<FormMessage />
									</FormItem>
								</FormField>

								<FormField v-slot="{ componentField }" name="payment_proof">
									<FormItem v-auto-animate>
										<FormLabel>Payment Screenshot</FormLabel>
										<FormControl>
											<ImageUpload
												v-bind="componentField"
												@images="
													(imagesArray) => {
														form.setFieldValue('payment_proof', imagesArray);
													}
												"
											/>
										</FormControl>
										<FormMessage />
									</FormItem>
								</FormField>

								<Button type="submit" class="w-full" :disabled="inertiaForm.processing">
									{{ inertiaForm.processing ? "Submitting..." : "Submit Payment" }}
								</Button>
							</form>
						</div>
					</div>
				</div>
			</ScrollArea>

			<!-- Add a note for historical views -->
			<Alert v-if="historical && payment" variant="info" class="mt-4">
				<AlertDescription class="text-sm">
					This is a historical view of the payment as it was at the time of {{ formatDate(payment.created_at) }}.
				</AlertDescription>
			</Alert>
		</DialogContent>
	</Dialog>
</template>
