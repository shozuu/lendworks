<script setup>
import { computed, ref } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import { format } from "date-fns";

const props = defineProps({
	rental: Object,
	userRole: String,
	lenderSchedules: Array,
});

const selectForm = useForm({
	return_datetime: "",
	start_time: "",
	end_time: "",
});

const emit = defineEmits(["schedule-selected"]);

const handleSelectDate = (slot) => {
	// Format the return datetime by combining the context date with slot time
	const returnDate = new Date(returnDateContext.value);
	const [hours, minutes] = slot.start_time.split(":");
	returnDate.setHours(parseInt(hours), parseInt(minutes), 0, 0);

	// Set form data before making the request
	selectForm.return_datetime = returnDate.toISOString();
	selectForm.start_time = slot.start_time;
	selectForm.end_time = slot.end_time;

	// Debug log to verify data
	console.log("Submitting schedule data:", {
		return_datetime: selectForm.return_datetime,
		start_time: selectForm.start_time,
		end_time: selectForm.end_time,
		lender_schedule: slot.original.id,
	});

	selectForm.patch(
		route("return-schedules.select", {
			rental: props.rental.id,
			lender_schedule: slot.original.id,
		}),
		{
			preserveScroll: true,
			onSuccess: () => {
				console.log("Schedule selected successfully");
				emit("schedule-selected");
			},
			onError: (errors) => {
				console.error("Schedule selection failed:", errors);
			},
		}
	);
};

// Use the same time slot formatting as PickupDateSelector
const formatScheduleTime = (schedule) => {
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

	return `${formatTimeString(schedule.start_time)} to ${formatTimeString(
		schedule.end_time
	)}`;
};

// Parse time helper
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

// Update isPastTimeSlot to match PickupDateSelector logic
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

// Update getScheduleDate to use return date
const getScheduleDate = (schedule) => {
	const returnDate = new Date(returnDateContext.value);
	const dayOfWeek = format(returnDate, "EEEE");

	// Only return date if schedule matches return day
	if (schedule.day_of_week === dayOfWeek) {
		return returnDate;
	}

	return null;
};

// Add time slot generation helper
const generateTimeSlots = (schedule) => {
	const { hours: startHour, minutes: startMin } = parseTime(schedule.start_time);
	const { hours: endHour, minutes: endMin } = parseTime(schedule.end_time);

	const slots = [];
	const slotDuration = 60; // Change to 1 hour per slot

	let currentSlotStart = startHour * 60 + startMin;
	const endTime = endHour * 60 + endMin;

	while (currentSlotStart < endTime) {
		const slotEnd = Math.min(currentSlotStart + slotDuration, endTime);

		slots.push({
			id: schedule.id,
			day_of_week: schedule.day_of_week,
			start_time: `${Math.floor(currentSlotStart / 60)
				.toString()
				.padStart(2, "0")}:${(currentSlotStart % 60).toString().padStart(2, "0")}`,
			end_time: `${Math.floor(slotEnd / 60)
				.toString()
				.padStart(2, "0")}:${(slotEnd % 60).toString().padStart(2, "0")}`,
			original: schedule,
		});

		currentSlotStart = slotEnd;
	}

	return slots;
};

// Update availableTimeSlots to match PickupDateSelector logic
const availableTimeSlots = computed(() => {
	const returnDate = new Date(returnDateContext.value);
	const now = new Date();
	const dayOfWeek = format(returnDate, "EEEE");

	return props.lenderSchedules
		?.filter((schedule) => {
			// Must be active and match return day
			if (!schedule.is_active || schedule.day_of_week !== dayOfWeek) {
				return false;
			}

			// If it's today, check if time slot hasn't passed
			if (format(now, "yyyy-MM-dd") === format(returnDate, "yyyy-MM-dd")) {
				return !isPastTimeSlot(schedule);
			}

			return true;
		})
		.flatMap((schedule) => generateTimeSlots(schedule))
		.map((slot) => ({
			...slot,
			formattedTime: formatScheduleTime(slot),
		}))
		.sort((a, b) => a.start_time.localeCompare(b.start_time));
});

const returnDateContext = computed(() => {
	if (props.rental.is_overdue) {
		return new Date();
	}

	if (props.rental.status === "pending_return") {
		const timelineEvent = props.rental.timeline_events.find(
			(e) => e.event_type === "return_initiated"
		);
		if (timelineEvent) {
			return new Date(timelineEvent.created_at);
		}
	}

	return new Date(props.rental.end_date);
});

const contextMessage = computed(() => {
	if (props.rental.is_overdue) {
		return `Select a time slot to return the item today`;
	}

	if (props.rental.status === "pending_return") {
		return "Choose when you would like to return the item";
	}

	return `Select a time slot to return the item on the end date`;
});

const suggestedTimeSlots = computed(() => {
  const returnDate = new Date(returnDateContext.value);
  const now = new Date();
  const slots = [];

  // Compare dates for today's logic
  const isReturnDateToday = 
    format(returnDate, "yyyy-MM-dd") === format(now, "yyyy-MM-dd");

  let startHour;
  if (isReturnDateToday) {
    const currentHour = now.getHours();
    if (currentHour >= 8 && currentHour < 20) {
      startHour = currentHour + 1;
    } else if (currentHour < 8) {
      startHour = 8;
    } else {
      return [];
    }
  } else {
    startHour = 8;
  }

  const endHour = 20;

  if (startHour < endHour) {
    for (let hour = startHour; hour < endHour; hour++) {
      const startDate = new Date(returnDate);
      startDate.setHours(hour, 0, 0);
      const endDate = new Date(returnDate);
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
  return_datetime: returnDateContext.value,
});

const selectedSlot = ref(null);

const handleSelectSlot = (slot) => {
  selectedSlot.value = slot;
};

const handleSuggestSchedule = () => {
  if (!selectedSlot.value) return;

  suggestForm.start_time = selectedSlot.value.start_time;
  suggestForm.end_time = selectedSlot.value.end_time;

  // Set return datetime by combining context date with selected time
  const returnDate = new Date(returnDateContext.value);
  const [hours, minutes] = selectedSlot.value.start_time.split(":");
  returnDate.setHours(parseInt(hours), parseInt(minutes), 0, 0);
  suggestForm.return_datetime = returnDate.toISOString();

  // Make sure this route name matches exactly what's defined in web.php
  suggestForm.post(
    route("return-schedules.suggest", {
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
</script>

<template>
	<Card class="shadow-sm">
		<CardHeader class="bg-card border-b">
			<CardTitle>Return Schedule</CardTitle>
			<p class="text-muted-foreground text-sm">{{ contextMessage }}</p>
			<div class="mt-2 p-3 bg-muted rounded-lg">
				<div class="flex items-baseline justify-between">
					<span class="font-medium">{{ format(returnDateContext, "EEEE") }}</span>
					<span class="text-sm text-muted-foreground">
						{{ format(returnDateContext, "MMMM d, yyyy") }}
					</span>
				</div>
			</div>
		</CardHeader>
		<CardContent class="p-0">
			<div class="px-6 py-4">
				<!-- Regular Time Slots -->
				<div v-if="availableTimeSlots.length" class="space-y-4">
					<h4 class="text-sm font-medium">Available Time Slots</h4>
					<div class="bg-muted/50 p-3 rounded-lg">
						<div class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
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

				<!-- Suggestion Section -->
				<div v-if="showSuggestionForm" class="space-y-6 mt-6 pt-6 border-t">
					<div class="space-y-2">
						<h4 class="text-sm font-medium">Suggest Alternative Time</h4>
						<p class="text-sm text-muted-foreground">
							No available slots found. You can suggest a preferred return time:
						</p>
					</div>

					<div class="bg-muted/50 p-3 rounded-lg">
						<div class="grid sm:grid-cols-2 gap-3 max-h-[300px] overflow-y-auto px-1">
							<div
								v-for="slot in suggestedTimeSlots"
								:key="slot.start_time"
								:class="[
									'flex items-center p-3 rounded-lg border transition-colors cursor-pointer bg-background',
									selectedSlot?.start_time === slot.start_time
										? 'border-primary bg-primary/5'
										: 'hover:border-muted-foreground/25',
								]"
								@click="handleSelectSlot(slot)"
							>
								<p class="text-sm">{{ slot.formattedTime }}</p>
							</div>
						</div>

						<!-- Suggest Button -->
						<Button
							v-if="selectedSlot"
							class="w-full mt-4"
							:disabled="suggestForm.processing"
							@click="handleSuggestSchedule"
						>
							{{ suggestForm.processing ? "Suggesting..." : "Suggest Time" }}
						</Button>
					</div>
				</div>

				<!-- No Slots Message -->
				<p
					v-if="!availableTimeSlots.length && !showSuggestionForm"
					class="text-muted-foreground py-8 text-center text-sm"
				>
					No available time slots for this date.
				</p>
			</div>
		</CardContent>
	</Card>
</template>
