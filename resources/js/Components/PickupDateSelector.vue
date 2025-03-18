<script setup>
import { computed, ref } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import { format, addDays } from "date-fns";

const props = defineProps({
	rental: Object,
	userRole: String,
	lenderSchedules: Array,
});

const selectForm = useForm({
	start_time: "",
	end_time: "",
});

const emit = defineEmits(["schedule-selected"]);

const handleSelectDate = (slot) => {
	console.log("Selecting slot:", slot); // Add logging to debug

	// Set form data before submission
	selectForm.start_time = slot.start_time;
	selectForm.end_time = slot.end_time;

	selectForm.patch(
		route("pickup-schedules.select", {
			rental: props.rental.id,
			lender_schedule: slot.original.id, // Use the original schedule's ID
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				emit("schedule-selected");
			},
			onError: (errors) => {
				console.error("Selection failed:", errors); // Add error logging
			},
		}
	);
};

const parseTime = (timeStr) => {
	const [hours, minutes] = timeStr.split(":");
	return { hours: parseInt(hours), minutes: parseInt(minutes) };
};

const daysMap = {
	Monday: 1,
	Tuesday: 2,
	Wednesday: 3,
	Thursday: 4,
	Friday: 5,
	Saturday: 6,
	Sunday: 0,
};

// Move isPastTimeSlot to be schedule-independent first check
const isPastTimeSlot = (schedule) => {
	const now = new Date();
	const today = now.getDay();
	const currentHour = now.getHours();
	const currentMinute = now.getMinutes();

	const { hours: endHour, minutes: endMinute } = parseTime(schedule.end_time);

	// If it's the same day, check if current time is past the end time
	if (daysMap[schedule.day_of_week] === today) {
		return (
			currentHour > endHour || (currentHour === endHour && currentMinute > endMinute)
		);
	}

	return false;
};

// Update getScheduleDate to check against rental start date
const getScheduleDate = (schedule) => {
	const rentalStartDate = new Date(props.rental.start_date);
	const dayOfWeek = format(rentalStartDate, "EEEE"); // Gets day name like "Monday"

	// Only return date if schedule matches rental start day
	if (schedule.day_of_week === dayOfWeek) {
		return rentalStartDate;
	}

	return null;
};

const selectedSchedule = computed(() => {
	if (!props.rental.pickup_schedules?.length) return null;
	return props.rental.pickup_schedules.find((s) => s.is_selected);
});

// Update the formatScheduleTime function to handle selected schedules correctly
const formatScheduleTime = (schedule) => {
	if (!schedule) return "";

	const formatTimeString = (timeStr) => {
		if (!timeStr) return "";
		const [hours, minutes] = timeStr.split(":");
		const date = new Date();
		date.setHours(parseInt(hours), parseInt(minutes));
		return date.toLocaleTimeString("en-US", {
			hour: "numeric",
			minute: "2-digit",
			hour12: true,
		});
	};

	// For selected schedule
	if (schedule.pickup_datetime) {
		// First try to find the original schedule
		const originalSchedule = props.lenderSchedules.find(
			(s) => s.id === schedule.lender_pickup_schedule_id
		);

		// If we found the original schedule, use its times
		if (originalSchedule) {
			return `${formatTimeString(originalSchedule.start_time)} to ${formatTimeString(
				originalSchedule.end_time
			)}`;
		}

		// Otherwise use the times stored in the pickup schedule itself
		return `${formatTimeString(schedule.start_time)} to ${formatTimeString(
			schedule.end_time
		)}`;
	}

	// For regular schedules in the list
	return `${formatTimeString(schedule.start_time)} to ${formatTimeString(
		schedule.end_time
	)}`;
};

// Update selectedScheduleDetails to use the correct schedule reference
const selectedScheduleDetails = computed(() => {
	if (!selectedSchedule.value) return null;

	const pickupDate = new Date(selectedSchedule.value.pickup_datetime);
	const scheduleDate = new Date(selectedSchedule.value.created_at);

	// Use the selected schedule's stored time frame directly
	return {
		dayOfWeek: format(pickupDate, "EEEE"),
		date: format(pickupDate, "MMMM d, yyyy"),
		time: formatScheduleTime(selectedSchedule.value),
		selectedOn: format(scheduleDate, "MMM d, yyyy h:mm a"),
	};
});

// Add new helper function for time slot generation
const generateTimeSlots = (schedule) => {
	const slots = [];

	// Parse the start and end time
	const { hours: startHour, minutes: startMin } = parseTime(schedule.start_time);
	const { hours: endHour, minutes: endMin } = parseTime(schedule.end_time);

	// Convert to total minutes for easier calculation
	let currentMinutes = startHour * 60 + startMin;
	const endMinutes = endHour * 60 + endMin;

	// Generate 1-hour slots until we can't fit another full hour
	while (currentMinutes + 60 <= endMinutes) {
		const slotStartTime = `${Math.floor(currentMinutes / 60)
			.toString()
			.padStart(2, "0")}:${(currentMinutes % 60).toString().padStart(2, "0")}`;

		const slotEndTime = `${Math.floor((currentMinutes + 60) / 60)
			.toString()
			.padStart(2, "0")}:${((currentMinutes + 60) % 60).toString().padStart(2, "0")}`;

		slots.push({
			id: schedule.id,
			day_of_week: schedule.day_of_week,
			start_time: slotStartTime,
			end_time: slotEndTime,
			original: schedule,
		});

		currentMinutes += 60;
	}

	return slots;
};

// Update availableTimeSlots computed to use time slots
const availableTimeSlots = computed(() => {
	console.log("Rental start date:", props.rental.start_date);
	console.log("Lender schedules:", props.lenderSchedules);

	const rentalStartDate = new Date(props.rental.start_date);
	const now = new Date();
	const dayOfWeek = format(rentalStartDate, "EEEE");

	const slots = props.lenderSchedules
		.filter((schedule) => {
			// Debug logs
			console.log("Checking schedule:", schedule);
			console.log("Schedule day:", schedule.day_of_week);
			console.log("Rental day:", dayOfWeek);
			console.log("Is active:", schedule.is_active);

			// Must be active and match rental start day
			return schedule.is_active && schedule.day_of_week === dayOfWeek;
		})
		.flatMap((schedule) => {
			// Generate 1-hour slots for each valid schedule
			const { hours: startHour, minutes: startMin } = parseTime(schedule.start_time);
			const { hours: endHour, minutes: endMin } = parseTime(schedule.end_time);

			const slots = [];
			const slotDuration = 60; // 1 hour slots

			let currentSlotStart = startHour * 60 + startMin;
			const endTime = endHour * 60 + endMin;

			while (currentSlotStart + slotDuration <= endTime) {
				const slotEndTime = currentSlotStart + slotDuration;

				const slot = {
					id: schedule.id,
					day_of_week: schedule.day_of_week,
					start_time: `${Math.floor(currentSlotStart / 60)
						.toString()
						.padStart(2, "0")}:${(currentSlotStart % 60).toString().padStart(2, "0")}`,
					end_time: `${Math.floor(slotEndTime / 60)
						.toString()
						.padStart(2, "0")}:${(slotEndTime % 60).toString().padStart(2, "0")}`,
					original: schedule,
				};

				// Only add non-past slots for today
				if (format(now, "yyyy-MM-dd") === format(rentalStartDate, "yyyy-MM-dd")) {
					const currentHour = now.getHours();
					const slotHour = Math.floor(currentSlotStart / 60);
					if (slotHour > currentHour) {
						slots.push(slot);
					}
				} else {
					slots.push(slot);
				}

				currentSlotStart = slotEndTime;
			}

			return slots;
		})
		.map((slot) => ({
			...slot,
			formattedTime: `${formatTimeString(slot.start_time)} to ${formatTimeString(
				slot.end_time
			)}`,
		}))
		.sort((a, b) => a.start_time.localeCompare(b.start_time));

	console.log("Generated time slots:", slots);
	return slots;
});

// Update formatTimeString helper for better time display
const formatTimeString = (timeStr) => {
	if (!timeStr) return "";
	const [hours, minutes] = timeStr.split(":");
	const date = new Date();
	date.setHours(parseInt(hours), parseInt(minutes));
	return date.toLocaleTimeString("en-US", {
		hour: "numeric",
		minute: "2-digit",
		hour12: true,
	});
};

const showSuggestionForm = computed(() => {
	return (
		props.userRole === "renter" &&
		(!availableTimeSlots.value.length ||
			availableTimeSlots.value.every((slot) => isPastTimeSlot(slot)))
	);
});

const suggestForm = useForm({
	start_time: "",
	end_time: "",
	pickup_datetime: props.rental.start_date,
});

// Fix the handleSuggestSchedule to explicitly set the pickup_datetime
const handleSuggestSchedule = () => {
	if (!selectedSlot.value) return;

	// Set times from selected slot
	suggestForm.start_time = selectedSlot.value.start_time;
	suggestForm.end_time = selectedSlot.value.end_time;

	// Get rental start date at midnight
	const rentalStartDate = new Date(props.rental.start_date);
	rentalStartDate.setHours(0, 0, 0, 0);

	// Create pickup datetime by adding the selected time to rental start date
	const [hours, minutes] = selectedSlot.value.start_time.split(":");
	const pickupDateTime = new Date(rentalStartDate);
	pickupDateTime.setHours(parseInt(hours), parseInt(minutes), 0, 0);

	// Set the pickup datetime
	suggestForm.pickup_datetime = pickupDateTime.toISOString();

	suggestForm.post(
		route("pickup-schedules.suggest", {
			rental: props.rental.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				selectedSlot.value = null;
				emit("schedule-selected");
			},
			onError: (errors) => {
				console.error("Failed to suggest schedule:", errors);
			},
		}
	);
};

// Generate time slots for suggestion (1-hour blocks from now until end of day)
const suggestedTimeSlots = computed(() => {
	const rentalStartDate = new Date(props.rental.start_date);
	const now = new Date();
	const slots = [];

	// Compare dates by normalizing them to YYYY-MM-DD for comparison
	const isStartDateToday =
		format(rentalStartDate, "yyyy-MM-dd") === format(now, "yyyy-MM-dd");

	// If suggesting for rental start date, determine start hour
	let startHour;
	if (isStartDateToday) {
		// If current hour is between 8 AM and 8 PM, start from next hour
		const currentHour = now.getHours();
		if (currentHour >= 8 && currentHour < 20) {
			startHour = currentHour + 1;
		} else if (currentHour < 8) {
			// If before 8 AM, start at 8 AM
			startHour = 8;
		} else {
			// If after 8 PM, no slots available
			return [];
		}
	} else {
		// For future dates, always start at 8 AM
		startHour = 8;
	}

	const endHour = 20; // End at 8 PM

	// Only generate slots if we have a valid start hour
	if (startHour < endHour) {
		for (let hour = startHour; hour < endHour; hour++) {
			const startDate = new Date(rentalStartDate);
			startDate.setHours(hour, 0, 0);
			const endDate = new Date(rentalStartDate);
			endDate.setHours(hour + 1, 0, 0);

			slots.push({
				start_time: `${hour.toString().padStart(2, "0")}:00`,
				end_time: `${(hour + 1).toString().padStart(2, "0")}:00`,
				formattedTime: `${format(startDate, "h:mm a")} - ${format(endDate, "h:mm a")}`,
			});
		}
	}

	return slots;
});

const selectedSlot = ref(null);

const handleSelectSlot = (slot) => {
	selectedSlot.value = slot;
};

// Add success message after slot selection
const handleConfirmSelection = () => {
	if (!selectedSlot.value) return;

	selectForm.start_time = selectedSlot.value.start_time;
	selectForm.end_time = selectedSlot.value.end_time;

	selectForm.patch(
		route("pickup-schedules.select", {
			rental: props.rental.id,
			lender_schedule: selectedSlot.value.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				emit("schedule-selected");
				selectedSlot.value = null;
			},
		}
	);
};

const isSuggestedSchedule = computed(() => {
	if (!selectedSchedule.value) return false;
	return selectedSchedule.value.is_suggested;
});
</script>

<template>
	<Card class="shadow-sm">
		<CardHeader class="bg-card border-b">
			<CardTitle>
				{{ userRole === "lender" ? "Schedule Confirmation" : "Pickup Schedule" }}
			</CardTitle>
			<!-- Show selected schedule info for lender -->
			<div
				v-if="selectedSchedule && userRole === 'lender'"
				class="mt-2 p-3 bg-muted rounded-lg"
			>
				<p class="text-sm text-muted-foreground">
					The renter has selected the following time slot:
				</p>
			</div>
		</CardHeader>
		<CardContent class="p-6">
			<div v-if="!isSuggestedSchedule">
				<!-- Time Slots Grid -->
				<div v-if="availableTimeSlots.length" class="space-y-6">
					<h4 class="text-base font-medium">Available Time Slots</h4>
					<div class="grid sm:grid-cols-2 gap-3 max-h-[300px] overflow-y-auto px-1">
						<div
							v-for="slot in availableTimeSlots"
							:key="`${slot.id}-${slot.start_time}`"
							:class="[
								'flex items-center p-3 rounded-lg border transition-colors cursor-pointer',
								selectedSlot?.start_time === slot.start_time &&
								selectedSlot?.end_time === slot.end_time
									? 'border-primary bg-primary/5'
									: 'hover:border-muted-foreground/25',
							]"
							@click="handleSelectSlot(slot)"
						>
							<div class="flex-1">
								<p class="font-medium text-sm">{{ slot.formattedTime }}</p>
							</div>
							<div
								v-if="
									selectedSlot?.start_time === slot.start_time &&
									selectedSlot?.end_time === slot.end_time
								"
								class="text-primary"
							>
								<CheckIcon class="w-5 h-5" />
							</div>
						</div>
					</div>

					<!-- Add Confirm Button -->
					<Button
						v-if="selectedSlot"
						class="w-full mt-4"
						:disabled="selectForm.processing"
						@click="handleConfirmSelection"
					>
						{{ selectForm.processing ? "Confirming..." : "Confirm Schedule" }}
					</Button>
				</div>

				<!-- No slots message -->
				<p v-else class="text-muted-foreground text-center py-4">
					No available time slots for this date.
				</p>

				<!-- Suggestion Section -->
				<div v-if="showSuggestionForm" class="space-y-6 mt-6 pt-6 border-t">
					<div class="space-y-2">
						<h4 class="text-base font-medium">Suggest Alternative Time</h4>
						<p class="text-sm text-muted-foreground">
							No available slots found. You can suggest a preferred meeting time:
						</p>
					</div>

					<div class="grid sm:grid-cols-2 gap-3 max-h-[300px] overflow-y-auto px-1">
						<div
							v-for="slot in suggestedTimeSlots"
							:key="slot.start_time"
							:class="[
								'flex items-center p-3 rounded-lg border transition-colors cursor-pointer',
								selectedSlot?.start_time === slot.start_time
									? 'border-primary bg-primary/5'
									: 'hover:border-muted-foreground/25',
							]"
							@click="handleSelectSlot(slot)"
						>
							<p class="font-medium text-sm">{{ slot.formattedTime }}</p>
						</div>
					</div>

					<!-- Add Confirm Button for Suggestions -->
					<Button
						v-if="selectedSlot"
						class="w-full"
						:disabled="suggestForm.processing"
						@click="handleSuggestSchedule"
					>
						{{ suggestForm.processing ? "Suggesting..." : "Suggest Time" }}
					</Button>
				</div>
			</div>
		</CardContent>
	</Card>
</template>
