<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import { ref, onMounted, onUnmounted, watch } from "vue";
import axios from "axios";

defineOptions({ layout: AuthLayout });

const videoRef = ref(null);
const cameras = ref([]);
const selectedCamera = ref("");
const matchScore = ref(null);
const verified = ref(null);
const error = ref(null);
const isSubmitting = ref(false);
let stream = null;

// Liveness detection states
const isLivenessActive = ref(false);
const livenessStep = ref(null);
const livenessVerified = ref(false);
const livenessProgress = ref(0);
const livenessImage = ref(null);
const livenessInstructions = ref("");
const idDataValidated = ref(false);

const livenessSteps = [
	{ action: "smile", instruction: "Please smile naturally" },
	{ action: "blink", instruction: "Please blink your eyes slowly" },
];

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

			try {
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
			} catch (apiError) {
				// Handle rate limiting (429) specifically
				if (apiError.response?.status === 429) {
					const cooldownMinutes = apiError.response.data.cooldown_minutes || 30;
					throw new Error(
						`Too many verification attempts. Please try again in ${cooldownMinutes} minutes.`
					);
				}
				throw apiError;
			}
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

const checkIdDataValidity = async () => {
	try {
		const response = await axios.get("/check-id-data");
		idDataValidated.value = response.data.valid;

		if (!idDataValidated.value) {
			// Redirect back to ID verification page if no valid ID data exists
			window.location.href = "/verify-id";
		}
	} catch (err) {
		console.error("Failed to check ID data:", err);
		error.value = "Error verifying your session data. Please try again.";
		// Redirect to ID verification
		setTimeout(() => {
			window.location.href = "/verify-id";
		}, 2000);
	}
};

const handleSubmit = async () => {
	if (!livenessVerified.value || !livenessImage.value) {
		error.value = "Please complete the liveness verification first";
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

		try {
			const result = await axios.post("/complete-verification", formData, {
				headers: {
					"Content-Type": "multipart/form-data",
				},
			});

			const data = result.data;
			matchScore.value = data.average_match_score;
			verified.value = data.verified;

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
		} catch (apiError) {
			// Handle specific error codes
			if (apiError.response?.status === 429) {
				const cooldownMinutes = apiError.response.data.cooldown_minutes || 30;
				error.value = `Too many verification attempts. Please try again in ${cooldownMinutes} minutes.`;
			} else if (apiError.response?.data?.code === "duplicate_id") {
				error.value =
					apiError.response.data.message ||
					"These IDs have already been registered with another account.";
			} else {
				error.value =
					apiError.response?.data?.message ||
					apiError.response?.data?.error ||
					"An error occurred during verification";
			}
			throw apiError; // Propagate to outer catch
		}
	} catch (err) {
		console.error("Submission error:", err);
		if (!error.value) {
			// Only set if not already set in inner catch
			error.value = "An unexpected error occurred during verification.";
		}
	} finally {
		isSubmitting.value = false;
	}
};

onMounted(() => {
	initializeWebcam();
	checkIdDataValidity();
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
			<h2 class="text-2xl font-bold text-center text-primary">Face Verification</h2>
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
					<p class="font-medium text-primary">Step 2 of 2: Face Verification</p>
					<p class="text-sm text-muted-foreground mt-1">
						We need to verify that you're really you! Please follow the instructions to
						complete face verification.
					</p>
				</div>
			</div>
		</div>

		<form @submit.prevent="handleSubmit" class="space-y-6">
			<div class="space-y-4">
				<!-- Webcam section -->
				<div class="border-2 border-primary rounded-lg p-4 bg-card">
					<div class="flex items-center mb-2">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							class="text-primary font-medium mr-2"
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
						<h3 class="text-primary font-medium">Face Scan</h3>
					</div>

					<!-- relative positioning container for video and overlay -->
					<div class="relative">
						<video
							ref="videoRef"
							autoplay
							playsinline
							class="w-full h-64 object-cover bg-primary rounded-lg scale-x-[-1]"
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
							<span class="text-primary px-2 py-1 rounded text-sm">
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
						<div v-if="livenessStep !== null" class="bg-accent p-4 rounded-lg">
							<p class="text-primary font-medium">{{ livenessInstructions }}</p>
							<div class="mt-2">
								<div class="w-full bg-primary/20 rounded-full h-2">
									<div
										class="bg-primary h-2 rounded-full"
										:style="{ width: `${livenessProgress}%` }"
									></div>
								</div>
							</div>
						</div>

						<button
							type="button"
							@click="startLivenessDetection"
							:disabled="isLivenessActive"
							class="w-full bg-primary text-primary-foreground py-2 px-4 rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50"
						>
							{{ isLivenessActive ? "Scanning in Progress..." : "Start Face Scan" }}
						</button>

						<select
							v-if="cameras.length > 1"
							v-model="selectedCamera"
							@change="switchCamera"
							class="w-full bg-muted border rounded-md p-2 text-foreground"
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
				<div v-if="livenessVerified" class="border rounded-lg p-4 bg-accent">
					<div class="flex items-center">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							class="text-emerald-400 mr-2"
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
						<p class="text-emerald-400 font-medium">Face Verified</p>
					</div>
				</div>

				<!-- Submit button -->
				<button
					type="submit"
					:disabled="!livenessVerified || isSubmitting"
					class="w-full bg-primary text-primary-foreground py-2 px-4 rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
				>
					{{ isSubmitting ? "Verifying..." : "Complete Verification" }}
				</button>
			</div>
		</form>

		<!-- Results section -->
		<div v-if="matchScore !== null" class="mt-6 p-4 bg-muted rounded-lg">
			<h3 class="font-medium mb-2">Verification Results</h3>
			<div class="flex items-center mt-2">
				<p>Status:</p>
				<span v-if="verified" class="flex items-center text-primary font-bold ml-1">
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
				<span v-else class="flex items-center text-destructive font-bold ml-1">
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
			class="mt-4 p-4 bg-destructive/10 text-destructive rounded-lg flex items-start"
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
