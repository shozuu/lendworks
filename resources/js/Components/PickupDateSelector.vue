<script setup>
import { computed } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import { formatDateTime } from "@/lib/formatters";
import { format, addDays } from "date-fns";

const props = defineProps({
	rental: Object,
	userRole: String,
	lenderSchedules: Array,
});

const selectForm = useForm({});

const emit = defineEmits(["schedule-selected"]);

const handleSelectDate = (schedule) => {
	selectForm.patch(
		route("pickup-schedules.select", {
			rental: props.rental.id,
			lender_schedule: schedule.id,
		}),
		{
			data: {
				metadata: {
					day_of_week: schedule.day_of_week,
					date: format(schedule.scheduleDate, "MMMM d, yyyy"),
					start_time: schedule.start_time,
					end_time: schedule.end_time,
				},
			},
			preserveScroll: true,
			onSuccess: () => {
				emit("schedule-selected");
			},
		}
	);
};

// Modified to get actual date based on day of week
const getScheduleDate = (dayOfWeek) => {
	const today = new Date();
	const daysMap = {
		Monday: 1,
		Tuesday: 2,
		Wednesday: 3,
		Thursday: 4,
		Friday: 5,
		Saturday: 6,
		Sunday: 0,
	};

	const currentDay = today.getDay();
	const targetDay = daysMap[dayOfWeek];
	let daysToAdd = targetDay - currentDay;

	if (daysToAdd < 0) {
		daysToAdd += 7;
	}

	const scheduleDate = new Date(today);
	scheduleDate.setDate(today.getDate() + daysToAdd);
	return scheduleDate;
};

// Modified availableSchedules computed property
const availableSchedules = computed(() => {
	const rentalStartDate = new Date(props.rental.start_date);
	rentalStartDate.setHours(23, 59, 59, 999); // End of rental start date

	return props.lenderSchedules
		.map((schedule) => {
			const scheduleDate = getScheduleDate(schedule.day_of_week);
			return {
				...schedule,
				scheduleDate,
				formattedTime: formatScheduleTime(schedule),
			};
		})
		.filter((schedule) => schedule.scheduleDate <= rentalStartDate)
		.sort((a, b) => a.scheduleDate - b.scheduleDate);
});

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

// Add this computed property to get available times for each day
const availableTimesForDay = computed(() => (dayOfWeek) => {
	return props.lenderSchedules
		.filter((schedule) => schedule.day_of_week === dayOfWeek)
		.map((schedule) => ({
			...schedule,
			formattedTime: formatScheduleTime(schedule),
		}));
});

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

// Add computed properties for week grouping
const schedulesGroupedByWeek = computed(() => {
	const rentalStartDate = new Date(props.rental.start_date);
	rentalStartDate.setHours(23, 59, 59, 999);

	const today = new Date();
	const grouped = {
		thisWeek: {},
		nextWeek: {},
	};

	props.lenderSchedules
		.filter((schedule) => {
			const scheduleDate = getScheduleDate(schedule.day_of_week);
			return scheduleDate <= rentalStartDate;
		})
		.forEach((schedule) => {
			const scheduleDate = getScheduleDate(schedule.day_of_week);
			const isThisWeek =
				scheduleDate.getTime() >= today.getTime() &&
				scheduleDate.getTime() < addDays(today, 7 - today.getDay()).getTime();

			const targetWeek = isThisWeek ? "thisWeek" : "nextWeek";
			const day = schedule.day_of_week;

			if (!grouped[targetWeek][day]) {
				grouped[targetWeek][day] = [];
			}

			grouped[targetWeek][day].push({
				...schedule,
				scheduleDate,
				formattedTime: formatScheduleTime(schedule),
			});
		});

	// Sort timeslots within each day
	Object.values(grouped).forEach((week) => {
		Object.values(week).forEach((slots) => {
			slots.sort((a, b) => a.start_time.localeCompare(b.start_time));
		});
	});

	return grouped;
});

const hasThisWeekSchedules = computed(
	() => Object.keys(schedulesGroupedByWeek.value.thisWeek).length > 0
);

const hasNextWeekSchedules = computed(
	() => Object.keys(schedulesGroupedByWeek.value.nextWeek).length > 0
);
</script>

<template>
	<Card class="shadow-sm">
		<CardHeader class="bg-card border-b">
			<CardTitle>Pickup Schedule</CardTitle>
			<p class="text-muted-foreground text-sm">
				Select a pickup date before or on
				{{ format(new Date(props.rental.start_date), "MMMM d, yyyy") }}
			</p>
		</CardHeader>
		<CardContent class="p-0">
			<!-- Remove padding from CardContent -->
			<!-- For Renter: Show available schedules -->
			<div
				v-if="userRole === 'renter' && !selectedSchedule"
				class="max-h-[400px] overflow-y-auto px-6 py-4 divide-y"
			>
				<!-- This Week -->
				<div v-if="hasThisWeekSchedules" class="space-y-4 pb-4">
					<h3 class="font-medium text-sm text-muted-foreground">This Week</h3>
					<div
						v-for="(slots, day) in schedulesGroupedByWeek.thisWeek"
						:key="day"
						class="space-y-3"
					>
						<div class="bg-muted/50 p-3 rounded-lg">
							<div class="flex items-baseline justify-between">
								<h4 class="font-medium">{{ day }}</h4>
								<p class="text-xs text-muted-foreground">
									{{ format(slots[0].scheduleDate, "MMM d, yyyy") }}
								</p>
							</div>
							<div class="mt-2 space-y-2">
								<div
									v-for="slot in slots"
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
				</div>

				<!-- Next Week -->
				<div v-if="hasNextWeekSchedules" class="space-y-4 pt-4">
					<h3 class="font-medium text-sm text-muted-foreground">Next Week</h3>
					<div
						v-for="(slots, day) in schedulesGroupedByWeek.nextWeek"
						:key="day"
						class="space-y-3"
					>
						<div class="bg-muted/50 p-3 rounded-lg">
							<div class="flex items-baseline justify-between">
								<h4 class="font-medium">{{ day }}</h4>
								<p class="text-xs text-muted-foreground">
									{{ format(slots[0].scheduleDate, "MMM d, yyyy") }}
								</p>
							</div>
							<div class="mt-2 space-y-2">
								<div
									v-for="slot in slots"
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
				</div>

				<p
					v-if="!hasThisWeekSchedules && !hasNextWeekSchedules"
					class="text-muted-foreground py-8 text-center text-sm"
				>
					No available schedules before the rental start date.
				</p>
			</div>

			<!-- Selected schedule display -->
			<div v-if="selectedSchedule" class="p-6 space-y-4">
				<div class="text-center">
					<h3 class="font-medium">Selected Pickup Schedule</h3>
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
