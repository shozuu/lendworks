<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import { ref, onMounted, onUnmounted } from "vue";
import axios from "axios";

defineOptions({ layout: AuthLayout });

const videoRef = ref(null);
const cameras = ref([]);
const selectedCamera = ref("");
const idCard = ref(null);
const idPreview = ref(null);
const selectedIdType = ref("");
const matchScore = ref(null);
const verified = ref(null);
const error = ref(null);
const isSubmitting = ref(false);
const idValidationResult = ref(null);
let stream = null;
//
const secondIdCard = ref(null);
const secondIdPreview = ref(null);
const secondSelectedIdType = ref("");
const secondIdValidationResult = ref(null);

// Liveness detection states
const isLivenessActive = ref(false);
const livenessStep = ref(null);
const livenessVerified = ref(false);
const livenessProgress = ref(0);
const livenessImage = ref(null);
const livenessInstructions = ref("");

const livenessSteps = [
	{ action: "smile", instruction: "Please smile naturally" },
	{ action: "blink", instruction: "Please blink your eyes slowly" },
];

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

// Configure axios defaults for CSRF
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
axios.defaults.withCredentials = true;

const initializeWebcam = async () => {
	try {
		const devices = await navigator.mediaDevices.enumerateDevices();
		cameras.value = devices.filter((device) => device.kind === "videoinput");

		if (cameras.value.length === 0) {
			throw new Error("No cameras found");
		}

		selectedCamera.value = cameras.value[0].deviceId;
		await startCamera();
	} catch (err) {
		error.value = "Failed to initialize webcam: " + err.message;
		console.error("Webcam initialization error:", err);
	}
};

const startCamera = async () => {
	try {
		if (stream) {
			stream.getTracks().forEach((track) => track.stop());
		}

		stream = await navigator.mediaDevices.getUserMedia({
			video: {
				deviceId: selectedCamera.value ? { exact: selectedCamera.value } : undefined,
			},
		});

		if (videoRef.value) {
			videoRef.value.srcObject = stream;
		}
	} catch (err) {
		error.value = "Failed to start camera: " + err.message;
		console.error("Camera start error:", err);
	}
};

const switchCamera = async () => {
	await startCamera();
};

const captureFrame = () => {
	const video = videoRef.value;
	const canvas = document.createElement("canvas");
	canvas.width = video.videoWidth;
	canvas.height = video.videoHeight;
	const ctx = canvas.getContext("2d");
	ctx.drawImage(video, 0, 0);
	return canvas.toDataURL("image/jpeg");
};

const startLivenessDetection = async () => {
	error.value = null;
	isLivenessActive.value = true;
	livenessStep.value = 0;
	livenessProgress.value = 0;
	livenessVerified.value = false;
	livenessInstructions.value = livenessSteps[0].instruction;

	try {
		for (let i = 0; i < livenessSteps.length; i++) {
			livenessStep.value = i;
			livenessInstructions.value = livenessSteps[i].instruction;

			// Wait for 2 seconds before capturing the frame
			await new Promise((resolve) => setTimeout(resolve, 2000));

			const frame = captureFrame();
			const formData = new FormData();

			// Convert base64 to blob
			const response = await fetch(frame);
			const blob = await response.blob();
			formData.append("image", blob);
			formData.append("action", livenessSteps[i].action);

			const result = await axios.post("/verify-liveness", formData, {
				headers: {
					"Content-Type": "multipart/form-data",
				},
			});

			if (!result.data.verified) {
				throw new Error(`Liveness check failed: ${result.data.message}`);
			}

			if (i === livenessSteps.length - 1) {
				livenessImage.value = frame;
			}

			livenessProgress.value = ((i + 1) / livenessSteps.length) * 100;
		}

		livenessVerified.value = true;
		isLivenessActive.value = false;
		livenessInstructions.value = "Liveness verification completed successfully!";
	} catch (err) {
		error.value = err.response?.data?.message || err.message;
		isLivenessActive.value = false;
		livenessStep.value = null;
		livenessProgress.value = 0;
	}
};

const handleSecondIdUpload = async (event) => {
	const file = event.target.files[0];
	if (file) {
		secondIdCard.value = file;
		secondIdPreview.value = URL.createObjectURL(file);

		if (secondSelectedIdType.value && idCard.value && selectedIdType.value) {
			await validateIdType();
		} else if (!secondSelectedIdType.value) {
			error.value = "Please select a second ID type before uploading";
		}
	}
};

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

const validateIdType = async () => {
	if (
		!idCard.value ||
		!selectedIdType.value ||
		!secondIdCard.value ||
		!secondSelectedIdType.value
	) {
		error.value = "Please select both ID types and upload both ID cards.";
		return;
	}

	error.value = null;

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

		console.log("=== PRIMARY ID OCR TEXT ===");
		console.log(response.data.primary_id.extracted_text);
		console.log("=== PRIMARY ID MATCHED KEYWORDS ===");
		console.log(response.data.primary_id.matched_keywords);

		console.log("=== SECONDARY ID OCR TEXT ===");
		console.log(response.data.secondary_id.extracted_text);
		console.log("=== SECONDARY ID MATCHED KEYWORDS ===");
		console.log(response.data.secondary_id.matched_keywords);

		// Check if both IDs are valid
		if (response.data.all_valid) {
			// Both IDs are valid, proceed with verification
		} else {
			error.value = "One or both IDs could not be verified.";
		}
	} catch (err) {
		console.error("ID validation error:", err);
		error.value =
			"Failed to validate IDs: " + (err.response?.data?.message || err.message);
	}
};

const handleSubmit = async () => {
	if (
		!livenessVerified.value ||
		!livenessImage.value ||
		!idCard.value ||
		!selectedIdType.value ||
		!idValidationResult.value?.isValid ||
		!secondIdCard.value ||
		!secondSelectedIdType.value ||
		!secondIdValidationResult.value?.isValid
	) {
		error.value =
			"Please complete liveness verification and upload two valid Philippine IDs";
		return;
	}

	isSubmitting.value = true;
	error.value = null;

	try {
		const formData = new FormData();

		// Convert base64 liveness image to blob
		const livenessResponse = await fetch(livenessImage.value);
		const livenessBlob = await livenessResponse.blob();
		formData.append("selfie", livenessBlob, "liveness.jpg");

		// Primary ID
		formData.append("id_card", idCard.value);
		formData.append("id_type", selectedIdType.value);

		// Secondary ID
		formData.append("id_card_secondary", secondIdCard.value);
		formData.append("id_type_secondary", secondSelectedIdType.value);

		const result = await axios.post("/verify-id", formData, {
			headers: {
				"Content-Type": "multipart/form-data",
			},
		});

		const data = result.data;
		matchScore.value = data.average_match_score;
		verified.value = data.verified; //log scores for both ID's

		// Add redirect after successful verification
		if (data.verified) {
			console.log("Verification successful, redirecting to:", data.redirect);
			setTimeout(() => {
				console.log("Executing redirect to:", data.redirect);
				window.location.href = data.redirect;
			}, 2000);
		} else {
			console.log("Verification failed, no redirect");
		}

		console.log("Primary ID match score:", data.primary_id.match_score);
		console.log("Secondary ID match score:", data.secondary_id.match_score);
		console.log("Average match score:", data.average_match_score);
		console.log("Both IDs verified:", data.verified);
	} catch (err) {
		console.error("Submission error:", err);
		error.value =
			err.response?.data?.message ||
			err.response?.data?.error ||
			"An error occurred during verification";
	} finally {
		isSubmitting.value = false;
	}
};

onMounted(() => {
	initializeWebcam();
});

onUnmounted(() => {
	if (stream) {
		stream.getTracks().forEach((track) => track.stop());
	}
});
</script>

<template>
	<div class="max-w-md mx-auto rounded-lg shadow-md p-6">
		<div class="flex items-center justify-center mb-6">
			<h2 class="text-2xl font-bold text-center text-blue-700">Verify your ID</h2>
		</div>

		<form @submit.prevent="handleSubmit" class="space-y-6">
			<div class="space-y-4">
				<!-- Webcam section -->
				<div class="border-2 border-blue-700 rounded-lg p-4 bg-white">
					<div class="flex items-center mb-2">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							class="text-blue-700 font-medium mr-2"
							width="20"
							height="20"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
						>
							<path
								d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"
							/>
							<circle cx="12" cy="13" r="3" />
						</svg>
						<h3 class="text-blue-700 font-medium">Face Scan</h3>
					</div>

					<!-- relative positioning container for video and overlay -->
					<div class="relative">
						<video
							ref="videoRef"
							autoplay
							playsinline
							class="w-full h-64 object-cover bg-blue-700 rounded-lg scale-x-[-1]"
						></video>

						<!-- this shows the circle overlay -->
						<div
							class="absolute inset-0 flex items-center justify-center pointer-events-none"
						>
							<div class="border-2 border-white rounded-full w-40 h-40 opacity-60"></div>
							<div
								class="absolute border-2 border-white rounded-full w-44 h-44 opacity-30"
								:class="{ 'animate-pulse': isLivenessActive }"
							></div>
						</div>

						<!-- Dynamic instruction text -->
						<div class="absolute bottom-2 inset-x-0 text-center">
							<span class="text-blue-500 px-2 py-1 rounded text-sm">
								<!-- Non active default instruction -->
								<template v-if="!isLivenessActive">
									Position your face within the circle
								</template>

								<!-- Dynamic instructions during liveness detection -->
								<template v-else>
									<span v-if="livenessStep === 0">Please smile naturally</span>
									<span v-else-if="livenessStep === 1"
										>Please blink your eyes naturally</span
									>
									<span v-else>Position your face within the circle</span>
								</template>
							</span>
						</div>
					</div>

					<div class="mt-4 space-y-2">
						<!-- Liveness Instructions -->
						<div v-if="livenessStep" class="bg-blue-50 p-4 rounded-lg">
							<p class="text-blue-700 font-medium">{{ livenessInstructions }}</p>
							<div class="mt-2">
								<div class="w-full bg-blue-200 rounded-full h-2">
									<div
										class="bg-blue-600 h-2 rounded-full"
										:style="{ width: `${livenessProgress}%` }"
									></div>
								</div>
							</div>
						</div>

						<button
							type="button"
							@click="startLivenessDetection"
							:disabled="isLivenessActive"
							class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
						>
							{{ isLivenessActive ? "Scanning in Progress..." : "Start Face Scan" }}
						</button>

						<select
							v-if="cameras.length > 1"
							v-model="selectedCamera"
							@change="switchCamera"
							class="w-full border rounded-md p-2 text-blue-700"
						>
							<option
								v-for="camera in cameras"
								:key="camera.deviceId"
								:value="camera.deviceId"
							>
								{{ camera.label || `Camera ${cameras.indexOf(camera) + 1}` }}
							</option>
						</select>
					</div>
				</div>

				<!-- Liveness verification result -->
				<div v-if="livenessVerified" class="border rounded-lg p-4 bg-green-50">
					<div class="flex items-center">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							class="text-green-500 mr-2"
							width="20"
							height="20"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							stroke-width="2"
							stroke-linecap="round"
							stroke-linejoin="round"
						>
							<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
							<polyline points="22 4 12 14.01 9 11.01" />
						</svg>
						<p class="text-green-700 font-medium">Face Verified</p>
					</div>
				</div>

				<!-- ID upload section -->
				<div class="border-2 border-blue-700 rounded-lg p-4 bg-white">
					<div class="flex items-center mb-2">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							class="text-blue-700 mr-2"
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
						<h3 class="font-medium text-blue-700">Upload Valid ID</h3>
					</div>

					<!-- ID Type Selection -->
					<div class="mb-4">
						<label class="block text-sm font-medium text-blue-700 mb-2">
							Select ID Type
						</label>
						<select
							v-model="selectedIdType"
							class="w-full border rounded-md p-2 text-blue-700 mb-3"
							@change="validateIdType"
						>
							<option value="">Select an ID type</option>
							<option
								v-for="(name, type) in validPhilippineIds"
								:key="type"
								:value="type"
							>
								{{ name }}
							</option>
						</select>
					</div>

					<input
						type="file"
						@change="handleIdUpload"
						accept="image/*"
						class="w-full border rounded-md p-2 text-blue-700"
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
										? 'bg-green-50 text-green-800'
										: 'bg-red-50 text-red-800'
								}`"
							>
								{{ idValidationResult.message }}
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- ID Type Selection 2 -->
			<!-- Second ID upload section -->
			<div class="border-2 border-blue-700 rounded-lg p-4 bg-white">
				<div class="flex items-center mb-2">
					<svg
						xmlns="http://www.w3.org/2000/svg"
						class="text-blue-700 mr-2"
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
					<h3 class="font-medium text-blue-700">Upload Second ID</h3>
				</div>

				<div class="mb-4">
					<label class="block text-sm font-medium text-blue-700 mb-2">
						Select ID Type
					</label>
					<select
						v-model="secondSelectedIdType"
						class="w-full border rounded-md p-2 text-blue-700 mb-3"
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
					class="w-full border rounded-md p-2 text-blue-700"
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
									? 'bg-green-50 text-green-800'
									: 'bg-red-50 text-red-800'
							}`"
						>
							{{ secondIdValidationResult.message }}
						</div>
					</div>
				</div>
			</div>

			<!-- Submit button -->
			<button
				type="submit"
				:disabled="
					!livenessVerified ||
					!idCard ||
					!selectedIdType ||
					!idValidationResult?.isValid ||
					!secondIdCard ||
					!secondSelectedIdType ||
					!secondIdValidationResult?.isValid ||
					isSubmitting
				"
				class="w-full bg-blue-700 text-white py-2 px-4 rounded-md hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
			>
				{{ isSubmitting ? "Verifying..." : "Complete Verification" }}
			</button>
		</form>

		<!-- Results section -->
		<div v-if="matchScore !== null" class="mt-6 p-4 bg-gray-50 rounded-lg">
			<h3 class="font-medium mb-2">Verification Results</h3>
			<div class="flex items-center">
				<p class="text-lg">
					Match Score: <span class="font-bold">{{ matchScore }}%</span>
				</p>
			</div>
			<div class="flex items-center mt-2">
				<p>Status:</p>
				<span v-if="verified" class="flex items-center text-green-600 font-bold ml-1">
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="18"
						height="18"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
						class="mr-1"
					>
						<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
						<polyline points="22 4 12 14.01 9 11.01" />
					</svg>
					Approved
				</span>
				<span v-else class="flex items-center text-red-600 font-bold ml-1">
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="18"
						height="18"
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
						class="mr-1"
					>
						<circle cx="12" cy="12" r="10" />
						<line x1="15" x2="9" y1="9" y2="15" />
						<line x1="9" x2="15" y1="9" y2="15" />
					</svg>
					Failed
				</span>
			</div>
		</div>

		<!-- Error message -->
		<div
			v-if="error"
			class="mt-4 p-4 bg-red-100 text-red-700 rounded-lg flex items-start"
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
</template>
