<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
// Update this line to import formatNumber
import { formatDateTime, formatNumber } from "@/lib/formatters";
import { useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import ProofImageViewer from "@/Components/ProofImageViewer.vue";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import { DollarSign, Shield } from "lucide-vue-next";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	dispute: Object,
});

const resolutionTypes = [
	{ label: "Deduct from Deposit", value: "deposit_deducted" },
	{ label: "Reject Dispute", value: "rejected" },
];

const selectedResolutionType = ref("");

const updateForm = useForm({
	status: "",
	verdict: "",
	verdict_notes: "",
	resolution_type: "",
	deposit_deduction: 0,
	deposit_deduction_reason: "",
});

const handleUpdateStatus = (newStatus) => {
	updateForm.status = newStatus;
	// Change from post to submit to match the route
	updateForm.post(route("admin.disputes.update-status", props.dispute.id), {
		preserveScroll: true,
		onSuccess: () => {
			updateForm.reset();
		},
		onError: (errors) => {
			console.error('Status update failed:', errors);
		}
	});
};

const handleResolve = () => {
	// Create form data object with all required fields
	const formData = {
		verdict: updateForm.verdict,
		verdict_notes: updateForm.verdict_notes,
		resolution_type: updateForm.resolution_type,
		deposit_deduction: 0,
		deposit_deduction_reason: "",
	};

	// Add deduction specific data if applicable
	if (updateForm.resolution_type === "deposit_deducted") {
		formData.deposit_deduction = updateForm.deposit_deduction;
		formData.deposit_deduction_reason = updateForm.deposit_deduction_reason;
	} else {
		// For rejection case, ensure we have valid default values
		formData.deposit_deduction = 0;
		formData.deposit_deduction_reason = "Dispute rejected - No deduction applied";
	}

	updateForm.clearErrors();
	updateForm.post(route("admin.disputes.resolve", props.dispute.id), {
		preserveScroll: true,
		data: formData,
		onSuccess: () => {
			updateForm.reset();
			selectedVerdict.value = "";
			selectedReason.value = "";
			deductionType.value = "amount";
			deductionPercentage.value = 0;
		},
		onError: (errors) => {
			console.error("Submission errors:", errors);
		},
	});
};

// Add error handling
watch(
	() => updateForm.errors,
	(newErrors) => {
		if (Object.keys(newErrors).length > 0) {
			console.error("Form errors:", newErrors);
		}
	},
	{ deep: true }
);

const isDeductionValid = computed(() => {
	if (updateForm.resolution_type !== "deposit_deducted") return true;

	const amount = Number(updateForm.deposit_deduction);
	const maxAmount = props.dispute.rental.deposit_fee;

	return (
		amount > 0 &&
		amount <= maxAmount &&
		updateForm.deposit_deduction_reason.trim().length > 0
	);
});

const isFormValid = computed(() => {
	return (
		updateForm.resolution_type &&
		updateForm.verdict.trim().length > 0 &&
		updateForm.verdict_notes.trim().length > 0 &&
		isDeductionValid.value
	);
});

// Add predefined options
const predefinedVerdicts = [
	{ label: "Damage Confirmed - Full Deposit Deduction", value: "full_damage" },
	{ label: "Significant Damage - Major Deduction", value: "major_damage" },
	{ label: "Minor Damage - Partial Deduction", value: "minor_damage" },
	{ label: "Normal Wear and Tear - No Deduction", value: "normal_wear" },
	{ label: "No Damage Found - Dispute Rejected", value: "no_damage" },
	{ label: "Evidence Not Clear - Dispute Rejected", value: "unclear_evidence" },
	{ label: "Pre-existing Damage - Dispute Rejected", value: "pre_existing" },
	{ label: "Insufficient Evidence", value: "insufficient_evidence" },
	{ label: "Other", value: "custom" },
];

const predefinedReasons = [
	{
		label: "Severe physical damage requiring repair/replacement",
		value: "severe_damage",
	},
	{ label: "Multiple parts damaged or non-functional", value: "multiple_damage" },
	{ label: "Critical components missing or damaged", value: "critical_damage" },
	{
		label: "Unauthorized modifications affecting functionality",
		value: "unauthorized_mods",
	},
	{ label: "Cosmetic damage beyond normal wear", value: "cosmetic_damage" },
	{ label: "Water/liquid damage", value: "water_damage" },
	{ label: "Electronic/mechanical malfunction", value: "malfunction" },
	{ label: "Missing accessories or components", value: "missing_parts" },
	{ label: "Structural integrity compromised", value: "structural_damage" },
	{ label: "Software/data tampering", value: "software_damage" },
	{ label: "Hygiene issues requiring professional cleaning", value: "hygiene_issues" },
	{ label: "Other", value: "custom" },
];

const selectedVerdict = ref("");
const selectedReason = ref("");

// Add refs for deduction type
const deductionType = ref("amount"); // 'amount' or 'percentage'
const deductionPercentage = ref(0);

// Watch for predefined verdict changes
watch(selectedVerdict, (newValue) => {
	if (newValue === "custom") {
		updateForm.verdict = ""; // Clear for custom input
	} else {
		const verdict = predefinedVerdicts.find((v) => v.value === newValue);
		updateForm.verdict = verdict?.label || "";
	}
});

// Watch for predefined reason changes
watch(selectedReason, (newValue) => {
	if (newValue === "custom") {
		updateForm.deposit_deduction_reason = ""; // Clear for custom input
	} else {
		const reason = predefinedReasons.find((r) => r.value === newValue);
		updateForm.deposit_deduction_reason = reason?.label || "";
	}
});

// Add watch for percentage changes
watch(deductionPercentage, (newValue) => {
	if (deductionType.value === "percentage") {
		const percentage = Math.min(Math.max(0, Number(newValue)), 100);
		const amount = Math.floor((percentage / 100) * props.dispute.rental.deposit_fee);
		updateForm.deposit_deduction = amount;
	}
});

// Add watch for deduction type changes
watch(deductionType, (newValue) => {
	if (newValue === "percentage") {
		deductionPercentage.value = Math.floor(
			(updateForm.deposit_deduction / props.dispute.rental.deposit_fee) * 100
		);
	}
});

// Add computed properties for safe number formatting
const safeNumber = (value) => Number(value || 0);

const formattedNumbers = computed(() => {
	const rental = props.dispute?.rental;
	if (!rental) return {};

	const currentEarnings =
		rental.current_earnings ||
		rental.base_price - rental.discount - rental.service_fee + (rental.overdue_fee || 0);

	return {
		basePrice: formatNumber(rental.base_price),
		discount: formatNumber(rental.discount),
		serviceFee: formatNumber(rental.service_fee),
		overdueFee: formatNumber(rental.overdue_fee),
		depositFee: formatNumber(rental.deposit_fee),
		remainingDeposit: formatNumber(rental.remaining_deposit),
		deductionAmount: formatNumber(updateForm.deposit_deduction),
		currentEarnings: formatNumber(currentEarnings),
	};
});

const showCustomVerdict = computed(() => selectedVerdict.value === "custom");
const showCustomReason = computed(() => selectedReason.value === "custom");

// Add state for image gallery
const currentImageIndex = ref(0);
const showGallery = ref(false);

const allImages = computed(() => {
  if (!props.dispute) return [];
  const mainImage = { image_path: props.dispute.old_proof_path };
  const additionalImages = props.dispute.additional_images || [];
  return [mainImage, ...additionalImages];
});

const navigateImage = (direction) => {
  if (direction === 'next') {
    currentImageIndex.value = (currentImageIndex.value + 1) % allImages.value.length;
  } else {
    currentImageIndex.value = currentImageIndex.value === 0 
      ? allImages.value.length - 1 
      : currentImageIndex.value - 1;
  }
};
</script>

<template>
	<Head :title="`Dispute #${dispute.id} | Admin`" />

	<div class="space-y-6">
		<div class="flex items-center justify-between">
			<h2 class="text-2xl font-semibold tracking-tight">
				Dispute Details #{{ dispute.id }}
			</h2>
			<Link
				:href="route('admin.rental-transactions.show', dispute.rental.id)"
				class="text-sm text-muted-foreground hover:text-primary"
			>
				View Related Transaction #{{ dispute.rental.id }}
			</Link>
		</div>

		<div class="grid gap-6 md:grid-cols-2">
			<!-- Dispute Information -->
			<Card>
				<CardHeader>
					<CardTitle>Dispute Information</CardTitle>
				</CardHeader>
				<CardContent class="space-y-4">
					<div class="space-y-2">
						<h4 class="font-medium">Status</h4>
						<p
							:class="{
								'text-muted-foreground': dispute.status === 'pending',
								'text-primary': dispute.status === 'reviewed',
								'text-destructive': dispute.status === 'resolved',
							}"
						>
							{{ dispute.status.charAt(0).toUpperCase() + dispute.status.slice(1) }}
						</p>
					</div>

					<Separator />

					<div class="space-y-2">
						<h4 class="font-medium">Reason</h4>
						<p>{{ dispute.reason }}</p>
					</div>

					<div class="space-y-2">
						<h4 class="font-medium">Description</h4>
						<p class="text-sm">{{ dispute.description }}</p>
					</div>

					<!-- Replace the evidence photos section in template -->
					<div class="space-y-2">
					  <h4 class="font-medium">Evidence Photos</h4>
					  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
					    <div 
					      v-for="(image, index) in allImages" 
					      :key="index"
					      class="relative aspect-square rounded-lg overflow-hidden border bg-muted cursor-pointer hover:opacity-90 transition-opacity"
					    >
					      <ProofImageViewer 
					        :image-path="image.image_path" 
					        class="w-full h-full object-cover"
					      />
					      <div class="absolute inset-x-0 bottom-0 bg-black/50 p-2">
					        <p class="text-white text-xs text-center">
					          {{ index === 0 ? 'Main Evidence' : `Additional ${index}` }}
					        </p>
					      </div>
					    </div>
					  </div>
					</div>

					<div class="space-y-2">
						<h4 class="font-medium">Raised By</h4>
						<p>{{ dispute.raised_by_user?.name || "Unknown User" }}</p>
						<p class="text-sm text-muted-foreground">
							{{ formatDateTime(dispute.created_at) }}
						</p>
					</div>

					<Separator />

					<div class="space-y-2">
						<h4 class="font-medium">Involved Parties</h4>
						<div class="space-y-3 p-3 bg-muted rounded-lg">
							<!-- Lender Info -->
							<div>
								<p class="text-sm font-medium">Lender</p>
								<p class="text-sm">{{ dispute.rental.lender.name }}</p>
							</div>
							<!-- Renter Info -->
							<div>
								<p class="text-sm font-medium">Renter</p>
								<p class="text-sm">{{ dispute.rental.renter.name }}</p>
							</div>
						</div>
					</div>

					<Separator />
				</CardContent>
			</Card>

			<!-- Rental Proofs -->
			<Card>
				<CardHeader>
					<CardTitle>Transaction Proofs</CardTitle>
				</CardHeader>
				<CardContent class="space-y-6">
					<!-- Handover Proofs -->
					<div class="space-y-4">
						<h4 class="font-medium">Handover Proofs</h4>
						<div
							v-for="proof in dispute.rental.handover_proofs"
							:key="proof.id"
							class="space-y-2"
						>
							<p class="text-sm text-muted-foreground">
								{{ proof.type === "handover" ? "Lender Handover" : "Renter Receipt" }}
							</p>
							<ProofImageViewer :image-path="proof.proof_path" />
						</div>
					</div>

					<Separator />

					<!-- Return Proofs -->
					<div class="space-y-4">
						<h4 class="font-medium">Return Proofs</h4>
						<div
							v-for="proof in dispute.rental.return_proofs"
							:key="proof.id"
							class="space-y-2"
						>
							<p class="text-sm text-muted-foreground">
								{{ proof.type === "return" ? "Renter Return" : "Lender Receipt" }}
							</p>
							<ProofImageViewer :image-path="proof.proof_path" />
						</div>
					</div>
				</CardContent>
			</Card>
		</div>

		<!-- Admin Actions -->
		<div class="grid md:grid-cols-[2fr_1fr] gap-6">
			<!-- Main Form Card -->
			<Card v-if="dispute.status !== 'resolved'">
				<CardHeader>
					<CardTitle>Admin Actions</CardTitle>
				</CardHeader>
				<CardContent>
					<div class="space-y-4">
						<div v-if="dispute.status === 'pending'" class="flex gap-4">
							<Button
								variant="outline"
								@click="handleUpdateStatus('reviewed')"
								:disabled="updateForm.processing"
							>
								Mark as Reviewed
							</Button>
						</div>

						<div v-if="dispute.status === 'reviewed'" class="space-y-6">
							<!-- Resolution Type Selection -->
							<div class="space-y-2">
								<label class="text-sm font-medium">Resolution Type</label>
								<Select v-model="updateForm.resolution_type">
									<SelectTrigger class="w-full bg-background">
										<SelectValue placeholder="Select resolution type" />
									</SelectTrigger>
									<SelectContent>
										<SelectItem
											v-for="type in resolutionTypes"
											:key="type.value"
											:value="type.value"
										>
											{{ type.label }}
										</SelectItem>
									</SelectContent>
								</Select>
							</div>

							<!-- Deduction Details -->
							<div
								v-if="updateForm.resolution_type === 'deposit_deducted'"
								class="space-y-4 p-4 bg-muted/50 rounded-lg border"
							>
								<div class="space-y-4">
									<div class="space-y-2">
										<label class="text-sm font-medium">Deduction Type</label>
										<Select v-model="deductionType">
											<SelectTrigger class="w-full bg-background">
												<SelectValue placeholder="Select deduction type" />
											</SelectTrigger>
											<SelectContent>
												<SelectItem value="amount">Fixed Amount</SelectItem>
												<SelectItem value="percentage">Percentage</SelectItem>
											</SelectContent>
										</Select>
									</div>

									<div class="space-y-2">
										<label class="text-sm font-medium">
											{{
												deductionType === "amount"
													? "Deduction Amount"
													: "Deduction Percentage"
											}}
										</label>

										<!-- Amount Input -->
										<div v-if="deductionType === 'amount'" class="relative">
											<span
												class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground"
												>₱</span
											>
											<input
												type="number"
												v-model="updateForm.deposit_deduction"
												class="w-full p-2 pl-8 border rounded-md bg-background"
												:max="dispute.rental.deposit_fee"
												min="0"
												step="0.01"
											/>
										</div>

										<!-- Percentage Input -->
										<div v-else class="space-y-2">
											<div class="relative">
												<span
													class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
													>%</span
												>
												<input
													type="number"
													v-model="deductionPercentage"
													class="w-full p-2 pr-8 border rounded-md bg-background"
													min="0"
													max="100"
													step="1"
												/>
											</div>
											<div class="flex justify-between text-sm text-muted-foreground">
												<span>Amount to deduct:</span>
												<span>₱{{ updateForm.deposit_deduction.toLocaleString() }}</span>
											</div>
										</div>

										<p class="text-xs text-muted-foreground">
											Available deposit: ₱{{
												dispute.rental.remaining_deposit.toLocaleString()
											}}
										</p>
									</div>
								</div>

								<div class="space-y-2">
									<label class="text-sm font-medium">Deduction Reason</label>
									<Select v-model="selectedReason" class="mb-2">
										<SelectTrigger class="w-full bg-background">
											<SelectValue placeholder="Select or type custom reason" />
										</SelectTrigger>
										<SelectContent>
											<SelectItem
												v-for="reason in predefinedReasons"
												:key="reason.value"
												:value="reason.value"
											>
												{{ reason.label }}
											</SelectItem>
										</SelectContent>
									</Select>
									<textarea
										v-if="showCustomReason"
										v-model="updateForm.deposit_deduction_reason"
										rows="2"
										class="w-full p-2 border rounded-md bg-background resize-none"
										placeholder="Enter custom reason..."
									/>
								</div>
							</div>

							<!-- Verdict Section -->
							<div class="space-y-4 p-4 bg-muted/50 rounded-lg border">
								<div class="space-y-2">
									<label class="text-sm font-medium">Verdict</label>
									<Select v-model="selectedVerdict" class="mb-2">
										<SelectTrigger class="w-full bg-background">
											<SelectValue placeholder="Select verdict" />
										</SelectTrigger>
										<SelectContent>
											<SelectItem
												v-for="verdict in predefinedVerdicts"
												:key="verdict.value"
												:value="verdict.value"
											>
												{{ verdict.label }}
											</SelectItem>
										</SelectContent>
									</Select>
									<textarea
										v-if="showCustomVerdict"
										v-model="updateForm.verdict"
										rows="3"
										class="w-full p-2 border rounded-md bg-background resize-none"
										placeholder="Enter custom verdict..."
									/>
								</div>

								<div class="space-y-2">
									<label class="text-sm font-medium">Additional Notes</label>
									<textarea
										v-model="updateForm.verdict_notes"
										rows="3"
										class="w-full p-2 border rounded-md bg-background resize-none"
										placeholder="Add any clarifications, instructions, or additional information..."
									/>
								</div>
							</div>

							<!-- Submit Button -->
							<Button
								variant="default"
								@click="handleResolve"
								:disabled="updateForm.processing || !isFormValid"
								class="w-full"
							>
								{{ updateForm.processing ? "Submitting..." : "Submit Resolution" }}
							</Button>
						</div>
					</div>
				</CardContent>
			</Card>

			<!-- Reference Information -->
			<div class="space-y-6">
				<!-- Resolution Details Card -->
				<Card v-if="dispute.status === 'resolved'" class="shadow-sm">
					<CardHeader class="bg-card border-b">
						<CardTitle>Dispute Resolution</CardTitle>
					</CardHeader>
					<CardContent class="p-4">
						<div class="space-y-4">
							<!-- Dispute Raiser Info -->
							<div class="space-y-2">
								<h4 class="text-sm font-medium">Dispute Raised By</h4>
								<p
									class="text-sm"
									:class="{
										'text-blue-500':
											dispute.raised_by_user.id === dispute.rental.lender.id,
										'text-emerald-500':
											dispute.raised_by_user.id === dispute.rental.renter.id,
									}"
								>
									{{ dispute.raised_by_user.name }}
									({{
										dispute.raised_by_user.id === dispute.rental.lender.id
											? "Lender"
											: "Renter"
									}})
								</p>
							</div>

							<Separator />

							<!-- Resolution Details -->
							<div class="space-y-2">
								<h4 class="text-sm font-medium">Resolution Type</h4>
								<p
									class="text-sm"
									:class="{
										'text-destructive': dispute.resolution_type === 'rejected',
										'text-primary': dispute.resolution_type === 'deposit_deducted',
									}"
								>
									{{
										dispute.resolution_type === "rejected"
											? "Dispute Rejected"
											: "Deposit Deduction Applied"
									}}
								</p>
							</div>

							<!-- Admin's Verdict -->
							<div class="space-y-2">
								<h4 class="text-sm font-medium">Admin's Verdict</h4>
								<div class="p-3 bg-muted rounded-lg space-y-3">
									<div>
										<p class="text-sm font-medium">Decision:</p>
										<p class="text-sm">{{ dispute.verdict }}</p>
									</div>
									<div>
										<p class="text-sm font-medium">Additional Notes:</p>
										<p class="text-sm text-muted-foreground">
											{{ dispute.verdict_notes }}
										</p>
									</div>
								</div>
							</div>

							<!-- Update Deduction Details section -->
							<template v-if="dispute.resolution_type === 'deposit_deducted'">
								<Separator />
								<div class="space-y-3">
									<h4 class="text-sm font-medium">Deduction Details</h4>
									<!-- Add Amount Display -->
									<div class="p-3 bg-muted rounded-lg space-y-3">
										<div class="flex justify-between items-center">
											<span class="text-sm text-muted-foreground">Deduction Amount:</span>
											<span class="text-sm font-medium text-destructive">
												{{ formatNumber(dispute.deposit_deduction) }}
											</span>
										</div>
										<Separator class="my-2" />
										<div class="space-y-2">
											<p class="text-sm font-medium">Deduction Reason:</p>
											<p class="text-sm text-muted-foreground">
												{{ dispute.deposit_deduction_reason }}
											</p>
										</div>
									</div>
									<!-- Add original deposit info -->
									<p class="text-xs text-muted-foreground">
										Original Security Deposit:
										{{ formatNumber(dispute.rental.deposit_fee) }}
									</p>
								</div>
							</template>

							<!-- Resolution Info -->
							<div class="mt-4 p-3 bg-muted rounded-lg">
								<p class="text-xs text-muted-foreground">
									Resolved by {{ dispute.resolved_by?.name }} on
									{{ formatDateTime(dispute.resolved_at) }}
								</p>
							</div>
						</div>
					</CardContent>
				</Card>

				<!-- Lender Earnings Card -->
				<Card>
					<CardHeader>
						<CardTitle class="flex items-center gap-2">
							<DollarSign class="w-4 h-4 text-emerald-500" />
							Lender Earnings Breakdown
						</CardTitle>
					</CardHeader>
					<CardContent class="p-4">
						<div class="space-y-3">
							<div class="flex justify-between items-center">
								<span class="text-sm">Base Rental:</span>
								<span>{{ formattedNumbers.basePrice }}</span>
							</div>
							<div class="flex justify-between items-center text-destructive">
								<span class="text-sm">Discounts & Fees:</span>
								<span>- {{ formattedNumbers.discount }}</span>
							</div>
							<div
								v-if="dispute?.rental?.overdue_fee"
								class="flex justify-between items-center text-emerald-500"
							>
								<span class="text-sm">Overdue Fee:</span>
								<span>+ {{ formattedNumbers.overdueFee }}</span>
							</div>
							<div
								v-if="dispute.resolution_type === 'deposit_deducted'"
								class="flex justify-between items-center text-emerald-500"
							>
								<span class="text-sm">Deposit Deduction:</span>
								<span>+ {{ formatNumber(dispute.deposit_deduction) }}</span>
							</div>
							<Separator />
							<div class="flex justify-between items-center font-medium">
								<span>Total Earnings:</span>
								<span class="text-emerald-500">
									{{
										formatNumber(
											safeNumber(dispute.rental.base_price) -
												safeNumber(dispute.rental.discount) -
												safeNumber(dispute.rental.service_fee) +
												safeNumber(dispute.rental.overdue_fee) +
												(dispute.resolution_type === "deposit_deducted"
													? safeNumber(dispute.deposit_deduction)
													: 0)
										)
									}}
								</span>
							</div>
							<p class="text-xs text-muted-foreground">
								{{
									dispute.resolution_type === "deposit_deducted"
										? "Total earnings including deposit deduction"
										: "Total earnings after discounts and fees"
								}}
							</p>
						</div>
					</CardContent>
				</Card>

				<!-- Renter Deposit Card -->
				<Card>
					<CardHeader>
						<CardTitle class="flex items-center gap-2">
							<Shield class="w-4 h-4 text-blue-500" />
							Security Deposit Status
						</CardTitle>
					</CardHeader>
					<CardContent class="p-4">
						<div class="space-y-3">
							<div class="flex justify-between items-center">
								<span class="text-sm">Original Deposit:</span>
								<span>{{ formattedNumbers.depositFee }}</span>
							</div>
							<div
								v-if="dispute?.rental?.has_deductions"
								class="flex justify-between items-center text-destructive"
							>
								<span class="text-sm">Previous Deductions:</span>
								<span
									>- ₱{{
										safeNumber(
											dispute?.rental?.deposit_fee - dispute?.rental?.remaining_deposit
										).toLocaleString()
									}}</span
								>
							</div>
							<Separator />
							<div class="flex justify-between items-center font-medium">
								<span>Available Deposit:</span>
								<span class="text-blue-500">
									{{ formattedNumbers.remainingDeposit }}
								</span>
							</div>
							<p
								v-if="updateForm.resolution_type === 'deposit_deducted'"
								class="text-xs text-destructive"
							>
								- {{ formattedNumbers.deductionAmount }} (pending deduction)
							</p>
						</div>
					</CardContent>
				</Card>
			</div>
		</div>
	</div>
</template>

<style scoped>
textarea:focus,
input:focus {
	outline: none;
	border-color: hsl(var(--primary));
	ring: 1px solid hsl(var(--primary));
}
</style>
