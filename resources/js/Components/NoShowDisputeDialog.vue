<script setup>
import { ref, computed, watch } from "vue";
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import { Textarea } from "@/components/ui/textarea";
import ImageUpload from "@/Components/ImageUpload.vue";
import { formatDateTime } from "@/lib/formatters";
import { CheckIcon, ClockIcon, InfoIcon } from "lucide-vue-next";

const props = defineProps({
	show: Boolean,
	rental: {
		type: Object,
		required: true,
	},
	type: {
		type: String,
		required: true,
		validator: (value) => ["lender_no_show", "renter_no_show"].includes(value),
	},
	schedule: {
		type: Object,
		required: true,
		default: null, // Add default value
	},
});

const emit = defineEmits(["update:show"]);

const disputeForm = useForm({
	type: props.type,
	proof_image: null,
	description: "",
	schedule_id: props.schedule?.id || null, // Add null fallback
});

// Add watch to update schedule_id when schedule changes
watch(
	() => props.schedule,
	(newSchedule) => {
		if (newSchedule) {
			disputeForm.schedule_id = newSchedule.id;
		}
	},
	{ immediate: true }
);

const selectedImage = ref([]);
const showConfirmDialog = ref(false);

const isDescriptionValid = computed(() => {
	return disputeForm.description.trim().length >= 10;
});

const handleSubmitAttempt = () => {
	// Validate required fields before showing confirmation
	if (!isDescriptionValid.value || selectedImage.length === 0 || !props.schedule?.id) {
		return;
	}
	showConfirmDialog.value = true;
};

const handleConfirmSubmit = () => {
	disputeForm.proof_image = selectedImage.value[0];
	disputeForm.post(route("rentals.handover-dispute", props.rental.id), {
		preserveScroll: true,
		onSuccess: () => {
			emit("update:show", false);
			showConfirmDialog.value = false; // Fix: use .value for ref assignment
			disputeForm.reset();
			selectedImage.value = [];
		},
		onError: (errors) => {
			console.error("Failed to submit dispute:", errors);
		},
	});
};

// Add these computed properties after other computed properties
const disputeInfo = computed(() => {
	if (props.type === "lender_no_show") {
		return {
			title: "Report Lender No-Show",
			outcomes: [
				{
					type: "resolve",
					title: "Full Refund",
					description: "If proven valid, the rental will be cancelled with full refund",
					details: [
						"Admin will verify your location proof and timing",
						"Security deposit and rental fee will be refunded",
						"You can request a new rental after",
					],
				},
				{
					type: "wait",
					title: "Extended Duration",
					description: "If rescheduled, rental period will be extended by 1 day",
					details: [
						"New schedules need to be selected",
						"Original rental rates still apply",
						"No additional charges for extension",
					],
				},
			],
		};
	}

	return {
		title: "Report Renter No-Show",
		outcomes: [
			{
				type: "resolve",
				title: "Keep First Day Payment",
				description:
					"If proven valid, rental will be cancelled but first day payment kept",
				details: [
					"Admin will verify your location proof and timing",
					"Rest of rental fee and deposit will be refunded",
					"Item will be marked as available",
				],
			},
			{
				type: "wait",
				title: "Reschedule Handover",
				description: "New schedules can be selected if rescheduled",
				details: [
					"Original rental duration maintained",
					"No charges for rescheduling",
					"Original rates still apply",
				],
			},
		],
	};
});

const description = computed(() => {
	return props.type === "lender_no_show"
		? "Report that the lender did not show up at the agreed meetup location and time"
		: "Report that the renter did not show up at the agreed meetup location and time";
});
</script>

<template>
	<Dialog :open="show || showConfirmDialog" @update:open="emit('update:show', $event)">
		<DialogContent :class="{ 'max-w-2xl': showConfirmDialog }">
			<!-- Initial Form -->
			<div v-if="!showConfirmDialog">
				<DialogHeader>
					<DialogTitle>{{ disputeInfo.title }}</DialogTitle>
					<DialogDescription>{{ description }}</DialogDescription>
				</DialogHeader>

				<form @submit.prevent="handleSubmitAttempt" class="space-y-4">
					<div class="space-y-2">
						<label class="text-sm font-medium">Location Proof</label>
						<ImageUpload
							:maxFiles="1"
							@images="selectedImage = $event"
							:error="disputeForm.errors.proof_image"
							class="w-full aspect-video"
						/>
						<p class="text-xs text-muted-foreground">
							Please provide a photo showing you're at the meetup location
						</p>
					</div>

					<div class="space-y-2">
						<label class="text-sm font-medium">Description</label>
						<Textarea
							v-model="disputeForm.description"
							placeholder="Describe what happened..."
							:error="disputeForm.errors.description"
							rows="4"
							:class="{
								'border-red-500': disputeForm.description && !isDescriptionValid,
							}"
						/>
						<p class="text-xs text-muted-foreground">
							Please provide at least 10 characters describing the situation
						</p>
						<p
							v-if="disputeForm.description && !isDescriptionValid"
							class="text-xs text-destructive"
						>
							Description must be at least 10 characters long
						</p>
					</div>

					<div class="flex justify-end gap-2">
						<Button type="button" variant="outline" @click="emit('update:show', false)">
							Cancel
						</Button>
						<Button
							type="submit"
							variant="destructive"
							:disabled="
								!isDescriptionValid ||
								selectedImage.length === 0 ||
								!props.schedule?.id ||
								disputeForm.processing
							"
						>
							Submit Dispute
						</Button>
					</div>
				</form>
			</div>

			<!-- Confirmation View -->
			<div v-else>
				<DialogHeader>
					<DialogTitle>Confirm {{ disputeInfo.title }}</DialogTitle>
					<DialogDescription>
						Please review the possible outcomes before submitting your report:
					</DialogDescription>
				</DialogHeader>

				<div class="py-6 space-y-6">
					<div
						v-for="outcome in disputeInfo.outcomes"
						:key="outcome.type"
						class="space-y-3"
					>
						<div class="flex items-start gap-2">
							<div
								:class="[
									'p-1.5 rounded-full shrink-0',
									outcome.type === 'resolve' ? 'bg-blue-100' : 'bg-amber-100',
								]"
							>
								<component
									:is="outcome.type === 'resolve' ? CheckIcon : ClockIcon"
									class="w-4 h-4"
									:class="outcome.type === 'resolve' ? 'text-blue-600' : 'text-amber-600'"
								/>
							</div>
							<div>
								<h4 class="font-medium text-base">{{ outcome.title }}</h4>
								<p class="text-sm text-muted-foreground mt-1">
									{{ outcome.description }}
								</p>
							</div>
						</div>

						<div class="ml-8 pl-2 border-l-2 border-muted space-y-2">
							<div
								v-for="(detail, i) in outcome.details"
								:key="i"
								class="flex items-start gap-2"
							>
								<span class="text-primary text-sm">•</span>
								<span class="text-sm text-muted-foreground">{{ detail }}</span>
							</div>
						</div>
					</div>

					<div class="bg-muted/50 p-4 rounded-lg mt-4">
						<div class="flex items-start gap-2">
							<InfoIcon class="w-5 h-5 text-muted-foreground shrink-0 mt-0.5" />
							<div>
								<h4 class="font-medium text-sm">Important Note:</h4>
								<p class="text-sm text-muted-foreground mt-1">
									Our admin team will review this report, verify evidence and location
									proof before taking action. We will process this as quickly as possible
									to ensure minimal disruption.
								</p>
							</div>
						</div>
					</div>

					<div class="flex justify-end gap-2">
						<Button variant="outline" @click="showConfirmDialog = false">Back</Button>
						<Button
							variant="destructive"
							:disabled="disputeForm.processing"
							@click="handleConfirmSubmit"
						>
							{{ disputeForm.processing ? "Submitting..." : "Submit Dispute" }}
						</Button>
					</div>
				</div>
			</div>
		</DialogContent>
	</Dialog>
</template>
