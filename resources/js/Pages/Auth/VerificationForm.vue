<script setup>
import { ref, reactive, onMounted, watch, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import axios from "axios";
import debounce from "lodash/debounce";

defineOptions({ layout: AuthLayout });
// Form state
const form = useForm({
	// Personal information
	firstName: "",
	middleName: "",
	lastName: "",
	birthdate: "",
	gender: "",
	civilStatus: "",

	// Contact information
	mobileNumber: "",
	email: "",

	// Address information
	streetAddress: "",
	barangay: "",
	city: "",
	province: "",
	postalCode: "",

	// ID information
	primaryIdType: "",
	secondaryIdType: "",
	nationality: "",
});

// Loading states
const isLoading = ref(false);
const loadingMessage = ref("");

function getReadableIdName(idType) {
	const idTypes = {
		philsys: "PhilSys ID (National ID)",
		drivers: "Driver's License",
		passport: "Philippine Passport",
		voters: "Voter's ID",
		postal: "Postal ID",
		tin: "TIN ID",
		umid: "UMID",
		sss: "SSS ID",
		prc: "PRC ID",
		philhealth: "PhilHealth ID",
	};

	return idTypes[idType] || idType || "Not specified";
}

const displayPrimaryIdType = computed(() => {
	return getReadableIdName(form.primaryIdType);
});

const displaySecondaryIdType = computed(() => {
	return getReadableIdName(form.secondaryIdType);
});

// Address search
const addressQuery = ref("");
const addressSuggestions = ref([]);
const isSearchingAddress = ref(false);
const showSuggestions = ref(false);

// Debounced address search function
const searchAddress = debounce(async () => {
	if (addressQuery.value.length < 3) {
		addressSuggestions.value = [];
		return;
	}

	isSearchingAddress.value = true;
	showSuggestions.value = true;

	try {
		// Using our server-side proxy instead of calling OpenStreetMap directly
		const response = await axios.get("/api/location/search", {
			params: {
				q: addressQuery.value,
				format: "json",
				addressdetails: 1,
				countrycodes: "ph",
				limit: 5,
			},
		});

		addressSuggestions.value = response.data;
	} catch (error) {
		console.error("Address search error:", error);
		// Show friendly error message to user
	} finally {
		isSearchingAddress.value = false;
	}
}, 500);

// Handle address selection
const selectAddress = (address) => {
	// Extract address components from OSM data
	const addressDetails = address.address;

	// Map OSM address fields to our form fields
	if (addressDetails) {
		form.streetAddress = [
			addressDetails.house_number,
			addressDetails.road,
			addressDetails.suburb,
		]
			.filter(Boolean)
			.join(", ");

		form.city =
			addressDetails.city || addressDetails.town || addressDetails.municipality || "";
		form.province = addressDetails.state || addressDetails.province || "";
		form.barangay = addressDetails.neighbourhood || addressDetails.suburb || "";
		form.postalCode = addressDetails.postcode || "";
	}

	// Close suggestions
	showSuggestions.value = false;
	addressQuery.value = address.display_name;
};

// Get pre-filled data from ID verification
const loadExtractedData = async () => {
	isLoading.value = true;
	loadingMessage.value = "Loading your information from verified IDs...";

	try {
		const response = await axios.get("/verify-id/extracted-data");
		console.log("Received extracted data:", response.data);

		// Pre-fill form with data from OCR if available
		if (response.data) {
			// Fix the birthdate format for HTML date input (requires YYYY-MM-DD)
			let birthdate = response.data.birthdate || "";
			if (birthdate) {
				// Make sure it's in the YYYY-MM-DD format required by HTML date inputs
				try {
					// Parse the date string and format it properly
					const date = new Date(birthdate);
					if (!isNaN(date.getTime())) {
						birthdate = date.toISOString().split("T")[0]; // Format as YYYY-MM-DD
					}
				} catch (e) {
					console.error("Failed to parse birthdate:", birthdate, e);
					birthdate = "";
				}
			}

			// Fix the typo in your code - you were setting birthdate to lastName!
			console.log("Setting normalized values:", {
				birthdate: birthdate,
			});

			// Now set the values with proper formatting
			form.firstName = response.data.firstName || "";
			form.middleName = response.data.middleName || "";
			form.lastName = response.data.lastName || "";
			form.birthdate = birthdate; // FIXED: Use the formatted birthdate
			form.nationality = response.data.nationality || "Filipino";
			form.primaryIdType = response.data.primaryIdType || "";
			form.secondaryIdType = response.data.secondaryIdType || "";
			form.mobileNumber = response.data.mobileNumber || "";
			form.email = response.data.email || "";

			// Set civil status if empty
			if (!form.civilStatus) {
				form.civilStatus = "single"; // Default value
			}
		}
	} catch (error) {
		console.error("Failed to get extracted data:", error);
		console.error("Error details:", {
			message: error.message,
			response: error.response?.data,
		});
	} finally {
		isLoading.value = false;
		loadingMessage.value = "";
	}
};

// Watch for address query changes
watch(addressQuery, (newValue) => {
	if (newValue) {
		searchAddress();
	} else {
		addressSuggestions.value = [];
	}
});

// Submit form
const submit = () => {
	form.post(route("verification.form.submit"), {
		onSuccess: () => {
			// Redirect to dashboard or show success message
		},
	});
};

onMounted(() => {
	loadExtractedData();

	// Close address suggestions on click outside
	document.addEventListener("click", (e) => {
		if (!e.target.closest(".address-search-container")) {
			showSuggestions.value = false;
		}
	});
});
</script>

<template>
	<div class="max-w-4xl mx-auto p-6">
		<h1 class="text-2xl font-bold text-primary mb-6">Complete Your Profile</h1>

		<div v-if="isLoading" class="bg-accent p-4 rounded-lg mb-6 flex items-center">
			<svg
				class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary"
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
			<p class="text-primary">{{ loadingMessage }}</p>
		</div>

		<div v-else class="bg-accent p-4 rounded-lg mb-6">
			<div class="flex items-center">
				<svg
					class="text-primary mr-2"
					width="24"
					height="24"
					viewBox="0 0 24 24"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
				>
					<path
						d="M12 22C6.477 22 2 17.523 2 12C2 6.477 6.477 2 12 2C17.523 2 22 6.477 22 12C22 17.523 17.523 22 12 22ZM11 15V17H13V15H11ZM11 7V13H13V7H11Z"
						fill="currentColor"
					/>
				</svg>
				<p class="text-primary">
					<strong>Important:</strong> We've pre-filled some information from your IDs.
					Please review and correct if needed.
				</p>
			</div>
		</div>

		<div class="border-2 border-primary rounded-lg p-6 bg-card">
			<h2 class="text-xl font-semibold mb-4 text-primary">Verified ID Information</h2>

			<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<!-- Primary ID Type -->
				<div>
					<label class="block text-foreground text-sm font-medium mb-1"
						>Primary ID Type</label
					>
					<div class="flex items-center">
						<input
							type="text"
							v-model="displayPrimaryIdType"
							class="w-full text-foreground border bg-muted rounded-md p-2"
							readonly
						/>
						<input type="hidden" v-model="form.primaryIdType" />
					</div>
					<p class="text-sm text-muted-foreground mt-1">Used for verification</p>
				</div>

				<!-- Secondary ID Type -->
				<div>
					<label class="block text-foreground text-sm font-medium mb-1"
						>Secondary ID Type</label
					>
					<div class="flex items-center">
						<input
							type="text"
							v-model="displaySecondaryIdType"
							class="w-full text-foreground border bg-muted rounded-md p-2"
							readonly
						/>
						<input type="hidden" v-model="form.secondaryIdType" />
					</div>
					<p class="text-sm text-muted-foreground mt-1">Used for verification</p>
				</div>
			</div>
		</div>
		<br />

		<form @submit.prevent="submit" class="space-y-6">
			<!-- Personal Information Section -->
			<div class="border-2 border-primary rounded-lg p-6 bg-card">
				<h2 class="text-xl font-semibold mb-4 text-primary">Personal Information</h2>

				<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
					<!-- First Name -->
					<div>
						<label class="block text-foreground text-sm font-medium mb-1"
							>First Name*</label
						>
						<input
							type="text"
							v-model="form.firstName"
							class="w-full text-foreground border bg-muted rounded-md p-2 focus:ring-ring focus:border-primary"
							required
						/>
						<p v-if="form.errors.firstName" class="text-destructive text-xs mt-1">
							{{ form.errors.firstName }}
						</p>
					</div>

					<!-- Middle Name -->
					<div>
						<label class="block text-sm text-foreground font-medium mb-1"
							>Middle Name</label
						>
						<input
							type="text"
							v-model="form.middleName"
							class="w-full text-foreground border bg-muted rounded-md p-2 focus:ring-ring focus:border-primary"
						/>
					</div>

					<!-- Last Name -->
					<div>
						<label class="block text-foreground text-sm font-medium mb-1"
							>Last Name*</label
						>
						<input
							type="text"
							v-model="form.lastName"
							class="w-full border bg-muted text-foreground rounded-md p-2 focus:ring-ring focus:border-primary"
							required
						/>
						<p v-if="form.errors.lastName" class="text-destructive text-xs mt-1">
							{{ form.errors.lastName }}
						</p>
					</div>

					<!-- Birthdate -->
					<div>
						<label class="block text-foreground text-sm font-medium mb-1"
							>Birthdate*</label
						>
						<input
							type="date"
							v-model="form.birthdate"
							class="w-full bg-muted border text-foreground rounded-md p-2 focus:ring-ring focus:border-primary"
							required
						/>
						<p v-if="form.errors.birthdate" class="text-destructive text-xs mt-1">
							{{ form.errors.birthdate }}
						</p>
					</div>

					<!-- Gender -->
					<div>
						<label class="block text-foreground text-sm font-medium mb-1">Gender*</label>
						<select
							v-model="form.gender"
							class="w-full text-foreground bg-muted border rounded-md p-2 focus:ring-ring focus:border-primary"
							required
						>
							<option value="" disabled>Select gender</option>
							<option value="male">Male</option>
							<option value="female">Female</option>
							<option value="other">Prefer not to say</option>
						</select>
						<p v-if="form.errors.gender" class="text-destructive text-xs mt-1">
							{{ form.errors.gender }}
						</p>
					</div>

					<!-- Civil Status -->
					<div>
						<label class="block text-foreground text-sm font-medium mb-1"
							>Civil Status*</label
						>
						<select
							v-model="form.civilStatus"
							class="w-full border bg-muted text-foreground rounded-md p-2 focus:ring-ring focus:border-primary"
							required
						>
							<option value="" disabled>Select status</option>
							<option value="single">Single</option>
							<option value="married">Married</option>
							<option value="divorced">Divorced</option>
							<option value="widowed">Widowed</option>
							<option value="separated">Separated</option>
						</select>
						<p v-if="form.errors.civilStatus" class="text-destructive text-xs mt-1">
							{{ form.errors.civilStatus }}
						</p>
					</div>

					<!-- Nationality -->
					<div>
						<label class="block text-foreground text-sm font-medium mb-1"
							>Nationality*</label
						>
						<input
							type="text"
							v-model="form.nationality"
							class="w-full bg-muted text-foreground border rounded-md p-2 focus:ring-ring focus:border-primary"
							required
						/>
						<p v-if="form.errors.nationality" class="text-destructive text-xs mt-1">
							{{ form.errors.nationality }}
						</p>
					</div>
				</div>
			</div>

			<!-- Contact Information Section -->
			<div class="border-2 border-primary rounded-lg p-6 bg-card">
				<h2 class="text-xl font-semibold mb-4 text-primary">Contact Information</h2>

				<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
					<!-- Mobile Number -->
					<div>
						<label class="block text-foreground text-sm font-medium mb-1"
							>Mobile Number*</label
						>
						<input
							type="tel"
							v-model="form.mobileNumber"
							class="w-full bg-muted text-foreground border rounded-md p-2 focus:ring-ring focus:border-primary"
							placeholder="e.g., +639123456789"
							required
						/>
						<p v-if="form.errors.mobileNumber" class="text-destructive text-xs mt-1">
							{{ form.errors.mobileNumber }}
						</p>
					</div>

					<!-- Email Address -->
					<div>
						<label class="block text-foreground text-sm font-medium mb-1"
							>Email Address*</label
						>
						<input
							type="email"
							v-model="form.email"
							class="w-full bg-muted text-foreground border rounded-md p-2 focus:ring-ring focus:border-primary"
							required
						/>
						<p v-if="form.errors.email" class="text-destructive text-xs mt-1">
							{{ form.errors.email }}
						</p>
					</div>
				</div>
			</div>

			<!-- Address Section -->
			<div class="border-2 border-primary rounded-lg p-6 bg-card">
				<h2 class="text-xl font-semibold mb-4 text-primary">Address Information</h2>

				<div class="space-y-4">
					<!-- OpenStreetMap Address Search -->
					<div class="address-search-container relative">
						<label class="block text-foreground text-sm font-medium mb-1"
							>Search Address*</label
						>
						<div class="relative">
							<input
								type="text"
								v-model="addressQuery"
								class="w-full bg-muted border text-foreground rounded-md p-2 pr-10 focus:ring-ring focus:border-primary"
								placeholder="Start typing your address to search..."
							/>
							<div class="absolute inset-y-0 right-0 flex items-center pr-3">
								<svg
									v-if="isSearchingAddress"
									class="animate-spin h-5 w-5 text-muted-foreground"
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
								<svg
									v-else
									class="h-5 w-5 text-muted-foreground"
									xmlns="http://www.w3.org/2000/svg"
									viewBox="0 0 20 20"
									fill="currentColor"
								>
									<path
										fill-rule="evenodd"
										d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
										clip-rule="evenodd"
									/>
								</svg>
							</div>
						</div>

						<!-- Address suggestions dropdown -->
						<div
							v-if="showSuggestions && addressSuggestions.length > 0"
							class="absolute z-10 w-full text-foreground bg-card shadow-lg rounded-md mt-1 border max-h-60 overflow-y-auto"
						>
							<ul class="py-1">
								<li
									v-for="(suggestion, index) in addressSuggestions"
									:key="index"
									@click="selectAddress(suggestion)"
									class="px-4 py-2 hover:bg-accent cursor-pointer text-sm"
								>
									{{ suggestion.display_name }}
								</li>
							</ul>
						</div>

						<div
							v-if="
								showSuggestions &&
								addressQuery.length >= 3 &&
								addressSuggestions.length === 0 &&
								!isSearchingAddress
							"
							class="mt-1 text-sm text-muted-foreground"
						>
							No address matches found. Please try a different search.
						</div>
					</div>

					<!-- Manual Address Fields -->
					<div>
						<p class="text-sm text-muted-foreground italic mb-2">
							You can edit these details after selecting an address from the search
							results.
						</p>

						<!-- Street Address -->
						<div class="mb-3">
							<label class="block text-foreground text-sm font-medium mb-1"
								>Street Address*</label
							>
							<input
								type="text"
								v-model="form.streetAddress"
								class="w-full bg-muted text-foreground border rounded-md p-2 focus:ring-ring focus:border-primary"
								placeholder="House/Lot/Unit Number, Street Name"
								required
							/>
							<p v-if="form.errors.streetAddress" class="text-destructive text-xs mt-1">
								{{ form.errors.streetAddress }}
							</p>
						</div>

						<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
							<!-- Province -->
							<div>
								<label class="block text-foreground text-sm font-medium mb-1"
									>Province*</label
								>
								<input
									type="text"
									v-model="form.province"
									class="w-full bg-muted text-foreground border rounded-md p-2 focus:ring-ring focus:border-primary"
									placeholder="Province"
									required
								/>
								<p v-if="form.errors.province" class="text-destructive text-xs mt-1">
									{{ form.errors.province }}
								</p>
							</div>

							<!-- City/Municipality -->
							<div>
								<label class="block text-foreground text-sm font-medium mb-1"
									>City/Municipality*</label
								>
								<input
									type="text"
									v-model="form.city"
									class="w-full bg-muted text-foreground border rounded-md p-2 focus:ring-ring focus:border-primary"
									placeholder="City or Municipality"
									required
								/>
								<p v-if="form.errors.city" class="text-destructive text-xs mt-1">
									{{ form.errors.city }}
								</p>
							</div>
						</div>

						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<!-- Barangay -->
							<div>
								<label class="block text-foreground text-sm font-medium mb-1"
									>Barangay*</label
								>
								<input
									type="text"
									v-model="form.barangay"
									class="w-full bg-muted text-foreground border rounded-md p-2 focus:ring-ring focus:border-primary"
									placeholder="Barangay"
									required
								/>
								<p v-if="form.errors.barangay" class="text-destructive text-xs mt-1">
									{{ form.errors.barangay }}
								</p>
							</div>

							<!-- Postal Code -->
							<div>
								<label class="block text-foreground text-sm font-medium mb-1"
									>Postal Code*</label
								>
								<input
									type="text"
									v-model="form.postalCode"
									class="w-full bg-muted text-foreground border rounded-md p-2 focus:ring-ring focus:border-primary"
									placeholder="e.g., 1200"
									required
								/>
								<p v-if="form.errors.postalCode" class="text-destructive text-xs mt-1">
									{{ form.errors.postalCode }}
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<button
				type="submit"
				class="w-full bg-primary text-primary-foreground py-3 px-4 rounded-md hover:bg-primary/90 transition-colors"
				:disabled="form.processing"
			>
				<span v-if="form.processing">
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
					Submitting...
				</span>
				<span v-else>Complete Registration</span>
			</button>
		</form>
	</div>
</template>
