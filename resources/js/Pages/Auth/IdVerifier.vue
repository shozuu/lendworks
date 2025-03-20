<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import { ref, watch } from "vue";
import axios from "axios";

defineOptions({ layout: AuthLayout });

const idCard = ref(null);
const idPreview = ref(null);
const selectedIdType = ref("");
const idValidationResult = ref(null);
const error = ref(null);
const isSubmitting = ref(false);

const secondIdCard = ref(null);
const secondIdPreview = ref(null);
const secondSelectedIdType = ref("");
const secondIdValidationResult = ref(null);
const duplicateIdError = ref(null);
const idsVerified = ref(false);

// Valid Philippine IDs mapping
const validPhilippineIds = {
	philsys: "Philippine Identification System (PhilSys) ID",
	drivers: "Driver's License",
	passport: "Philippine Passport",
	sss: "SSS ID",
	gsis: "GSIS ID",
	postal: "Postal ID",
	voters: "Voter's ID",
	prc: "PRC ID",
	philhealth: "PhilHealth ID",
	tin: "TIN ID",
	umid: "UMID",
};

watch([selectedIdType, secondSelectedIdType], ([newPrimaryType, newSecondaryType]) => {
	if (newPrimaryType && newSecondaryType && newPrimaryType === newSecondaryType) {
		duplicateIdError.value = "Primary and secondary ID must be different types";
		// Reset the second ID if user selects the same as primary
		secondIdCard.value = null;
		secondIdPreview.value = null;
		secondIdValidationResult.value = null;
	} else {
		duplicateIdError.value = null;
	}
});

// Configure axios defaults for CSRF
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
axios.defaults.withCredentials = true;

const handleIdUpload = async (event) => {
	const file = event.target.files[0];
	if (file) {
		idCard.value = file;
		idPreview.value = URL.createObjectURL(file);

		if (selectedIdType.value) {
			await validateIdType();
		} else {
			error.value = "Please select an ID type before uploading";
		}
	}
};

const handleSecondIdUpload = async (event) => {
	const file = event.target.files[0];
	if (file) {
		// Check for duplicate ID types before proceeding
		if (secondSelectedIdType.value === selectedIdType.value) {
			error.value = "Primary and secondary ID must be different types.";
			duplicateIdError.value = "Primary and secondary ID must be different types";
			return;
		}

		secondIdCard.value = file;
		secondIdPreview.value = URL.createObjectURL(file);

		if (secondSelectedIdType.value && idCard.value && selectedIdType.value) {
			await validateIdType();
		} else if (!secondSelectedIdType.value) {
			error.value = "Please select a second ID type before uploading";
		}
	}
};

const validateIdType = async () => {
	if (!idCard.value || !selectedIdType.value) {
		error.value = "Please select the primary ID type and upload ID card.";
		return;
	}

	if (!secondIdCard.value || !secondSelectedIdType.value) {
		error.value = "Please select the secondary ID type and upload ID card.";
		return;
	}

	if (selectedIdType.value === secondSelectedIdType.value) {
		error.value = "Primary and secondary ID must be different types.";
		duplicateIdError.value = "Primary and secondary ID must be different types";
		return;
	}

	error.value = null;
	duplicateIdError.value = null;
	isSubmitting.value = true;

	try {
		const formData = new FormData();
		formData.append("id_card_primary", idCard.value);
		formData.append("id_type_primary", selectedIdType.value);
		formData.append("id_card_secondary", secondIdCard.value);
		formData.append("id_type_secondary", secondSelectedIdType.value);

		const response = await axios.post("/api/validate-id-type", formData, {
			headers: {
				"Content-Type": "multipart/form-data",
			},
		});

		idValidationResult.value = {
			isValid: response.data.primary_id.is_valid,
			message: response.data.primary_id.message,
		};

		secondIdValidationResult.value = {
			isValid: response.data.secondary_id.is_valid,
			message: response.data.secondary_id.message,
		};

		// Both IDs are valid
		if (response.data.all_valid) {
			idsVerified.value = true;
		} else {
			error.value = "One or both IDs could not be verified.";
			idsVerified.value = false;
		}
	} catch (err) {
		console.error("ID validation error:", err);
		error.value =
			"Failed to validate IDs: " + (err.response?.data?.message || err.message);
		idsVerified.value = false;
	} finally {
		isSubmitting.value = false;
	}
};

const proceedToLiveness = async () => {
	if (!idsVerified.value) {
		error.value = "Please validate both IDs before proceeding";
		return;
	}

	isSubmitting.value = true;
	error.value = null;

	try {
		// Store ID information in session
		const formData = new FormData();
		formData.append("id_card", idCard.value);
		formData.append("id_type", selectedIdType.value);
		formData.append("id_card_secondary", secondIdCard.value);
		formData.append("id_type_secondary", secondSelectedIdType.value);

		const response = await axios.post("/store-id-data", formData, {
			headers: {
				"Content-Type": "multipart/form-data",
			},
		});

		if (response.data.success) {
			// Redirect to liveness verification
			window.location.href = "/verify-liveness";
		} else {
			error.value = response.data.message || "Failed to store ID data";
		}
	} catch (err) {
		console.error("Error storing ID data:", err);
		if (err.response?.status === 429) {
			error.value =
				err.response.data.message || "Too many attempts. Please try again later.";
		} else if (err.response?.data?.code === "duplicate_id") {
			error.value =
				err.response.data.message ||
				"These IDs have already been registered with another account.";
		} else {
			error.value =
				err.response?.data?.message || "An error occurred during ID verification";
		}
	} finally {
		isSubmitting.value = false;
	}
};
</script>

<template>
	<div class="max-w-md mx-auto rounded-lg shadow-md p-6">
		<div class="flex items-center justify-center mb-6">
			<h2 class="text-2xl font-bold text-center text-primary">Step 1: Upload Your IDs</h2>
		</div>

		<!-- Add verification notice -->
		<div class="bg-accent border-l-4 border-primary p-4 mb-6 rounded-md">
			<div class="flex">
				<svg
					xmlns="http://www.w3.org/2000/svg"
					class="h-6 w-6 text-primary mr-2"
					fill="none"
					viewBox="0 0 24 24"
					stroke="currentColor"
				>
					<path
						stroke-linecap="round"
						stroke-linejoin="round"
						stroke-width="2"
						d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
					/>
				</svg>
				<div>
					<p class="font-medium text-primary">Step 1 of 2: Upload Your IDs</p>
					<p class="text-sm text-muted-foreground mt-1">
						Please upload two different valid Philippine government IDs for verification.
						This helps us verify your identity.
					</p>
				</div>
			</div>
		</div>

		<div class="space-y-6">
			<!-- ID upload section 1 -->
			<div class="border-2 border-primary rounded-lg p-4 bg-card">
				<div class="flex items-center mb-2">
					<svg
						xmlns="http://www.w3.org/2000/svg"
						class="text-primary mr-2"
						width="20"
						height="20"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
					>
						<rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
						<circle cx="9" cy="9" r="2" />
						<path d="M15 8h.01" />
						<path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
					</svg>
					<h3 class="font-medium text-primary">Upload Primary ID</h3>
				</div>

				<!-- ID Type Selection -->
				<div class="mb-4">
					<label class="block text-sm font-medium text-foreground mb-2">
						Select ID Type
					</label>
					<select
						v-model="selectedIdType"
						class="w-full border bg-muted rounded-md p-2 text-foreground mb-3 focus:border-primary focus:ring-ring"
						@change="validateIdType"
					>
						<option value="">Select an ID type</option>
						<option v-for="(name, type) in validPhilippineIds" :key="type" :value="type">
							{{ name }}
						</option>
					</select>
				</div>

				<input
					type="file"
					@change="handleIdUpload"
					accept="image/*"
					class="w-full border rounded-md p-2 text-foreground focus:border-primary focus:ring-ring"
				/>

				<div v-if="idPreview" class="mt-4">
					<img
						:src="idPreview"
						alt="ID preview"
						class="w-full h-48 object-cover rounded-lg"
					/>

					<div v-if="idValidationResult" class="mt-2">
						<div
							:class="`p-2 rounded text-sm ${
								idValidationResult.isValid
									? 'bg-accent text-emerald-400'
									: 'bg-destructive/10 text-destructive'
							}`"
						>
							{{ idValidationResult.message }}
						</div>
					</div>
				</div>
			</div>

			<!-- ID upload section 2 -->
			<div class="border-2 border-primary rounded-lg p-4 bg-card">
				<div class="flex items-center mb-2">
					<svg
						xmlns="http://www.w3.org/2000/svg"
						class="text-primary mr-2"
						width="20"
						height="20"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
					>
						<rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
						<circle cx="9" cy="9" r="2" />
						<path d="M15 8h.01" />
						<path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
					</svg>
					<h3 class="font-medium text-primary">Upload Secondary ID</h3>
				</div>

				<div class="mb-4">
					<label class="block text-sm font-medium text-foreground mb-2">
						Select ID Type
					</label>
					<select
						v-model="secondSelectedIdType"
						class="w-full bg-muted border rounded-md p-2 text-foreground mb-3 focus:border-primary focus:ring-ring"
						@change="validateIdType"
					>
						<option value="">Select an ID type</option>
						<option v-for="(name, type) in validPhilippineIds" :key="type" :value="type">
							{{ name }}
						</option>
					</select>
				</div>

				<input
					type="file"
					@change="handleSecondIdUpload"
					accept="image/*"
					class="w-full border rounded-md p-2 text-foreground focus:border-primary focus:ring-ring"
				/>

				<div v-if="secondIdPreview" class="mt-4">
					<img
						:src="secondIdPreview"
						alt="Second ID preview"
						class="w-full h-48 object-cover rounded-lg"
					/>

					<div v-if="secondIdValidationResult" class="mt-2">
						<div
							:class="`p-2 rounded text-sm ${
								secondIdValidationResult.isValid
									? 'bg-accent text-emerald-400'
									: 'bg-destructive/10 text-destructive'
							}`"
						>
							{{ secondIdValidationResult.message }}
						</div>
					</div>
				</div>
			</div>

			<!-- Next Step Button -->
			<button
				@click="proceedToLiveness"
				:disabled="!idsVerified || isSubmitting"
				class="w-full bg-primary text-primary-foreground py-3 px-4 rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
			>
				<span v-if="isSubmitting">
					<svg
						class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-foreground inline-block"
						xmlns="http://www.w3.org/2000/svg"
						fill="none"
						viewBox="0 0 24 24"
					>
						<circle
							class="opacity-25"
							cx="12"
							cy="12"
							r="10"
							stroke="currentColor"
							stroke-width="4"
						></circle>
						<path
							class="opacity-75"
							fill="currentColor"
							d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
						></path>
					</svg>
					Processing...
				</span>
				<span v-else>Continue to Face Verification</span>
			</button>

			<!-- Error message -->
			<div
				v-if="error"
				class="p-4 bg-destructive/10 text-destructive rounded-lg flex items-start"
			>
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="20"
					height="20"
					viewBox="0 0 24 24"
					fill="none"
					stroke="currentColor"
					stroke-width="2"
					stroke-linecap="round"
					stroke-linejoin="round"
					class="mr-2 flex-shrink-0 mt-0.5"
				>
					<circle cx="12" cy="12" r="10" />
					<line x1="12" x2="12" y1="8" y2="12" />
					<line x1="12" x2="12.01" y1="16" y2="16" />
				</svg>
				<p>{{ error }}</p>
			</div>
		</div>
	</div>
</template>
