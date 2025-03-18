<script setup>
import { computed } from "vue";
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
			lender_schedule: slot.id, // This was the issue - slot is a generated timeslot
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
	const { hours: startHour, minutes: startMin } = parseTime(schedule.start_time);
	const { hours: endHour, minutes: endMin } = parseTime(schedule.end_time);

	const slots = [];
	const slotDuration = 120; // 2 hours per slot

	let currentSlotStart = startHour * 60 + startMin;
	const endTime = endHour * 60 + endMin;

	while (currentSlotStart < endTime) {
		const slotEnd = Math.min(currentSlotStart + slotDuration, endTime);

		slots.push({
			id: schedule.id, // Make sure we keep the original schedule's ID
			day_of_week: schedule.day_of_week,
			start_time: `${Math.floor(currentSlotStart / 60)
				.toString()
				.padStart(2, "0")}:${(currentSlotStart % 60).toString().padStart(2, "0")}`,
			end_time: `${Math.floor(slotEnd / 60)
				.toString()
				.padStart(2, "0")}:${(slotEnd % 60).toString().padStart(2, "0")}`,
		});

		currentSlotStart = slotEnd;
	}

	return slots;
};

// Update availableTimeSlots computed to use time slots
const availableTimeSlots = computed(() => {
	const rentalStartDate = new Date(props.rental.start_date);
	const now = new Date();
	const dayOfWeek = format(rentalStartDate, "EEEE");

	return props.lenderSchedules
		.filter((schedule) => {
			// Must be active and match rental start day
			if (!schedule.is_active || schedule.day_of_week !== dayOfWeek) {
				return false;
			}

			// If it's today, check if time slot hasn't passed
			if (format(now, "yyyy-MM-dd") === format(rentalStartDate, "yyyy-MM-dd")) {
				return !isPastTimeSlot(schedule);
			}

			return true;
		})
		.flatMap((schedule) => generateTimeSlots(schedule))
		.map((slot) => ({
			...slot,
			scheduleDate: rentalStartDate,
			formattedTime: formatScheduleTime(slot),
		}))
		.sort((a, b) => a.start_time.localeCompare(b.start_time));
});
</script>

<template>
	<Card class="shadow-sm">
		<CardHeader class="bg-card border-b">
			<CardTitle>
				{{ userRole === "lender" ? "Confirm Schedule Selection" : "Pickup Schedule" }}
			</CardTitle>
			<p class="text-muted-foreground text-sm">
				{{
					userRole === "lender"
						? "The renter has selected this schedule from your available time slots"
						: `Select a pickup time for ${format(
								new Date(props.rental.start_date),
								"MMMM d, yyyy"
						  )}`
				}}
			</p>
		</CardHeader>
		<CardContent class="p-0">
			<!-- Show available time slots -->
			<div v-if="userRole === 'renter' && !selectedSchedule" class="px-6 py-4">
				<div v-if="availableTimeSlots.length" class="space-y-4">
					<div class="bg-muted/50 p-3 rounded-lg">
						<div class="flex items-baseline justify-between">
							<h4 class="font-medium">Available Time Slots</h4>
							<p class="text-xs text-muted-foreground">
								{{ format(availableTimeSlots[0].scheduleDate, "MMM d, yyyy") }}
							</p>
						</div>
						<div class="mt-2 space-y-2">
							<div
								v-for="slot in availableTimeSlots"
								:key="slot.id"
								class="flex items-center justify-between bg-background p-2 rounded"
							>
								<span class="text-sm">{{ slot.formattedTime }}</span>
								<Button
									size="sm"
									variant="outline"
									@click="handleSelectDate(slot)"
									:disabled="selectForm.processing"
								>
									Select
								</Button>
							</div>
						</div>
					</div>
				</div>
				<p v-else class="text-muted-foreground py-8 text-center text-sm">
					No available time slots for the rental start date.
				</p>
			</div>

			<!-- Update the selected schedule display -->
			<div v-if="selectedSchedule" class="p-6 space-y-4">
				<div class="text-center">
					<h3 class="font-medium">
						{{
							userRole === "lender"
								? "Selected Pickup Schedule"
								: "Your Selected Schedule"
						}}
					</h3>
				</div>

				<div class="p-4 border rounded-lg bg-muted/30">
					<div class="space-y-3">
						<div class="flex items-baseline justify-between">
							<h4 class="font-medium">{{ selectedScheduleDetails.dayOfWeek }}</h4>
							<span class="text-sm">{{ selectedScheduleDetails.date }}</span>
						</div>

						<div class="flex items-center justify-between">
							<span class="text-sm text-muted-foreground">Time Frame</span>
							<span class="font-medium">{{ selectedScheduleDetails.time }}</span>
						</div>

						<div
							class="pt-2 mt-2 border-t text-xs text-muted-foreground text-center italic"
						>
							Schedule selected on {{ selectedScheduleDetails.selectedOn }}
						</div>
						<div class="pt-2 mt-2 text-center text-primary text-sm font-medium">
							<div v-if="selectedSchedule.is_confirmed">
								✓ Schedule confirmed by lender
							</div>
							<div v-else>
								{{
									userRole === "lender"
										? "Please confirm this schedule to proceed with the handover"
										: "Waiting for lender confirmation..."
								}}
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Lender waiting message -->
			<div
				v-if="userRole === 'lender' && !selectedSchedule"
				class="p-6 text-center text-muted-foreground"
			>
				Waiting for renter to select a pickup schedule...
			</div>
		</CardContent>
	</Card>
</template>
