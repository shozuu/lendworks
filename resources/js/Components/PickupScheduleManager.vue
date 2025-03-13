<script setup>
import { ref, watch, computed } from "vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import { addDays, format, startOfWeek } from "date-fns";

const props = defineProps({
	schedules: Array,
});

const days = [
	"Monday",
	"Tuesday",
	"Wednesday",
	"Thursday",
	"Friday",
	"Saturday",
	"Sunday",
];

const hours = Array.from({ length: 24 }, (_, i) => ({
	value: i.toString().padStart(2, "0"),
	label: new Date(0, 0, 0, i).toLocaleString("en-US", { hour: "numeric", hour12: true }),
}));

const minutes = ["00", "15", "30", "45"];

const selectedDays = ref([]);
const defaultStartHour = ref("");
const defaultStartMinute = ref("");
const defaultEndHour = ref("");
const defaultEndMinute = ref("");
const timeError = ref("");

const bulkForm = useForm({
	schedules: [],
});

const validateBulkSchedules = () => {
	timeError.value = "";

	if (
		!defaultStartHour.value ||
		!defaultStartMinute.value ||
		!defaultEndHour.value ||
		!defaultEndMinute.value
	) {
		return false;
	}

	const start =
		parseInt(defaultStartHour.value) * 60 + parseInt(defaultStartMinute.value);
	const end = parseInt(defaultEndHour.value) * 60 + parseInt(defaultEndMinute.value);

	if (end <= start) {
		timeError.value = "End time must be later than start time";
		return false;
	}

	return true;
};

const isBulkFormValid = computed(() => {
	return validateBulkSchedules() && selectedDays.value.length > 0;
});

const handleBulkSubmit = () => {
	if (!validateBulkSchedules() || selectedDays.value.length === 0) {
		return;
	}

	bulkForm.schedules = selectedDays.value.map((day) => ({
		day_of_week: day,
		start_time: `${defaultStartHour.value}:${defaultStartMinute.value}`,
		end_time: `${defaultEndHour.value}:${defaultEndMinute.value}`,
	}));

	bulkForm.post(route("lender.pickup-schedules.store-bulk"), {
		preserveScroll: true,
		onSuccess: () => {
			selectedDays.value = [];
			defaultStartHour.value = "";
			defaultStartMinute.value = "";
			defaultEndHour.value = "";
			defaultEndMinute.value = "";
		},
	});
};

const deleteForm = useForm({});

const scheduleToDelete = ref(null);
const showDeleteDialog = ref(false);

const initiateDelete = (schedule) => {
	scheduleToDelete.value = schedule;
	showDeleteDialog.value = true;
};

const handleDelete = () => {
	if (!scheduleToDelete.value) return;

	deleteForm.delete(route("lender.pickup-schedules.destroy", scheduleToDelete.value.id), {
		preserveScroll: true,
		onSuccess: () => {
			showDeleteDialog.value = false;
			scheduleToDelete.value = null;
		},
	});
};

const dayOrder = {
	Monday: 1,
	Tuesday: 2,
	Wednesday: 3,
	Thursday: 4,
	Friday: 5,
	Saturday: 6,
	Sunday: 7,
};

const safeSchedules = computed(() => {
	const schedules = props.schedules || [];
	return [...schedules].sort((a, b) => {
		const dayDiff = dayOrder[a.day_of_week] - dayOrder[b.day_of_week];
		if (dayDiff !== 0) return dayDiff;
		return a.start_time.localeCompare(b.start_time);
	});
});

const editingSchedule = ref(null);
const editForm = useForm({
	day_of_week: "",
	start_time: "",
	end_time: "",
});

const editingStartHour = ref("");
const editingStartMinute = ref("");
const editingEndHour = ref("");
const editingEndMinute = ref("");

const startEditing = (schedule) => {
	editingSchedule.value = schedule;

	const [startHour, startMin] = schedule.start_time.split(":");
	const [endHour, endMin] = schedule.end_time.split(":");

	editingStartHour.value = startHour;
	editingStartMinute.value = startMin;
	editingEndHour.value = endHour;
	editingEndMinute.value = endMin;

	editForm.day_of_week = schedule.day_of_week;
	editForm.start_time = schedule.start_time;
	editForm.end_time = schedule.end_time;
};

const cancelEditing = () => {
	editingSchedule.value = null;
	editForm.reset();
	editingStartHour.value = "";
	editingStartMinute.value = "";
	editingEndHour.value = "";
	editingEndMinute.value = "";
	editTimeError.value = "";
};

watch([editingStartHour, editingStartMinute], () => {
	if (editingStartHour.value && editingStartMinute.value) {
		editForm.start_time = `${editingStartHour.value}:${editingStartMinute.value}`;
	}
});

watch([editingEndHour, editingEndMinute], () => {
	if (editingEndHour.value && editingEndMinute.value) {
		editForm.end_time = `${editingEndHour.value}:${editingEndMinute.value}`;
	}
});

const editTimeError = ref("");

const validateEditTimeRange = () => {
	editTimeError.value = "";

	if (
		!editingStartHour.value ||
		!editingStartMinute.value ||
		!editingEndHour.value ||
		!editingEndMinute.value
	) {
		return false;
	}

	const start =
		parseInt(editingStartHour.value) * 60 + parseInt(editingStartMinute.value);
	const end = parseInt(editingEndHour.value) * 60 + parseInt(editingEndMinute.value);

	if (end <= start) {
		editTimeError.value =
			"End time must be later than start time and within the same day";
		return false;
	}

	return true;
};

watch([editingStartHour, editingStartMinute, editingEndHour, editingEndMinute], () => {
	if (
		editingStartHour.value &&
		editingStartMinute.value &&
		editingEndHour.value &&
		editingEndMinute.value
	) {
		validateEditTimeRange();
	}
});

const isEditFormValid = computed(() => {
	if (
		!editForm.day_of_week ||
		!editingStartHour.value ||
		!editingStartMinute.value ||
		!editingEndHour.value ||
		!editingEndMinute.value
	) {
		return false;
	}

	const start =
		parseInt(editingStartHour.value) * 60 + parseInt(editingStartMinute.value);
	const end = parseInt(editingEndHour.value) * 60 + parseInt(editingEndMinute.value);

	return end > start && !editTimeError.value;
});

const handleUpdate = () => {
	if (!validateEditTimeRange()) {
		return;
	}

	editForm.patch(route("lender.pickup-schedules.update", editingSchedule.value.id), {
		preserveScroll: true,
		onSuccess: () => {
			cancelEditing();
			editTimeError.value = "";
		},
	});
};

const getNextOccurrence = (dayOfWeek) => {
	const today = new Date();
	today.setHours(0, 0, 0, 0);

	const startOfCurrentWeek = startOfWeek(today, { weekStartsOn: 1 });
	const daysMap = {
		Monday: 0,
		Tuesday: 1,
		Wednesday: 2,
		Thursday: 3,
		Friday: 4,
		Saturday: 5,
		Sunday: 6,
	};

	const dayIndex = daysMap[dayOfWeek];
	const nextDate = addDays(startOfCurrentWeek, dayIndex);

	if (nextDate < today) {
		return addDays(nextDate, 7);
	}

	return nextDate;
};

const formatScheduleTime = (schedule) => {
	const formatTimeString = (timeStr) => {
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

const currentWeekSchedules = computed(() => {
	const today = new Date();
	today.setHours(0, 0, 0, 0);
	const schedules = props.schedules || [];

	return [...schedules]
		.filter((schedule) => {
			const nextDate = getNextOccurrence(schedule.day_of_week);
			return isThisWeek(nextDate);
		})
		.sort((a, b) => {
			const dateA = getNextOccurrence(a.day_of_week);
			const dateB = getNextOccurrence(b.day_of_week);
			return dateA - dateB;
		});
});

const nextWeekSchedules = computed(() => {
	const today = new Date();
	today.setHours(0, 0, 0, 0);
	const schedules = props.schedules || [];

	return [...schedules]
		.filter((schedule) => {
			const nextDate = getNextOccurrence(schedule.day_of_week);
			return !isThisWeek(nextDate);
		})
		.sort((a, b) => {
			const dateA = getNextOccurrence(a.day_of_week);
			const dateB = getNextOccurrence(b.day_of_week);
			return dateA - dateB;
		});
});

const isThisWeek = (date) => {
	const today = new Date();
	const weekStart = startOfWeek(today, { weekStartsOn: 1 });
	const endOfWeek = addDays(weekStart, 6);
	return date >= weekStart && date <= endOfWeek;
};
</script>

<template>
	<div class="space-y-4">
		<h2 class="text-lg font-semibold">Pickup Schedule Management</h2>
		<p class="text-muted-foreground text-sm">
			Set your regular availability for item handovers
		</p>

		<div class="grid gap-6 md:grid-cols-2">
			<!-- Schedule Form Card -->
			<Card class="shadow-sm">
				<CardHeader class="bg-card border-b">
					<CardTitle>Add Availability</CardTitle>
					<p class="text-muted-foreground text-sm">
						Select days and times for your pickup schedules
					</p>
				</CardHeader>
				<CardContent class="p-6">
					<div class="space-y-6">
						<!-- Day Selection -->
						<div class="space-y-2">
							<label class="text-sm font-medium">Select Days</label>
							<div class="flex flex-wrap gap-2">
								<Button
									v-for="day in days"
									:key="day"
									variant="outline"
									size="sm"
									:class="{
										'bg-primary text-primary-foreground': selectedDays.includes(day),
									}"
									@click="
										selectedDays.includes(day)
											? (selectedDays = selectedDays.filter((d) => d !== day))
											: selectedDays.push(day)
									"
								>
									{{ day }}
								</Button>
							</div>
						</div>

						<!-- Time Range -->
						<div class="space-y-4">
							<h4 class="text-sm font-medium">Time Range</h4>
							<div class="grid gap-4">
								<!-- Start Time -->
								<div class="space-y-2">
									<label class="text-sm font-medium">From</label>
									<div class="grid grid-cols-2 gap-2">
										<Select v-model="defaultStartHour">
											<SelectTrigger>
												<SelectValue placeholder="Hour" />
											</SelectTrigger>
											<SelectContent>
												<SelectItem
													v-for="hour in hours"
													:key="hour.value"
													:value="hour.value"
												>
													{{ hour.label }}
												</SelectItem>
											</SelectContent>
										</Select>

										<Select v-model="defaultStartMinute">
											<SelectTrigger>
												<SelectValue placeholder="Min" />
											</SelectTrigger>
											<SelectContent>
												<SelectItem v-for="min in minutes" :key="min" :value="min">
													{{ min }}
												</SelectItem>
											</SelectContent>
										</Select>
									</div>
								</div>

								<!-- End Time -->
								<div class="space-y-2">
									<label class="text-sm font-medium">To</label>
									<div class="grid grid-cols-2 gap-2">
										<Select v-model="defaultEndHour">
											<SelectTrigger>
												<SelectValue placeholder="Hour" />
											</SelectTrigger>
											<SelectContent>
												<SelectItem
													v-for="hour in hours"
													:key="hour.value"
													:value="hour.value"
												>
													{{ hour.label }}
												</SelectItem>
											</SelectContent>
										</Select>

										<Select v-model="defaultEndMinute">
											<SelectTrigger>
												<SelectValue placeholder="Min" />
											</SelectTrigger>
											<SelectContent>
												<SelectItem v-for="min in minutes" :key="min" :value="min">
													{{ min }}
												</SelectItem>
											</SelectContent>
										</Select>
									</div>
								</div>
							</div>
						</div>

						<p v-if="timeError" class="text-destructive text-sm mt-1">
							{{ timeError }}
						</p>

						<Button
							class="w-full"
							:disabled="!isBulkFormValid || bulkForm.processing"
							@click="handleBulkSubmit"
						>
							Create Schedules
						</Button>
					</div>
				</CardContent>
			</Card>

			<!-- Current Schedules Card -->
			<Card class="shadow-sm">
				<CardHeader class="bg-card border-b">
					<CardTitle>Current Availability</CardTitle>
					<p class="text-muted-foreground text-sm">
						Your recurring schedules for item handovers
					</p>
				</CardHeader>
				<CardContent class="p-6">
					<div class="space-y-6">
						<!-- This Week's Schedules -->
						<div v-if="currentWeekSchedules.length" class="space-y-3">
							<h3 class="text-sm font-medium text-muted-foreground">This Week</h3>
							<div
								v-for="schedule in currentWeekSchedules"
								:key="schedule.id"
								class="p-4 border rounded-lg hover:bg-muted/50 transition-colors"
							>
								<!-- Existing schedule display code -->
								<div v-if="editingSchedule?.id === schedule.id" class="space-y-4">
									<div class="space-y-4">
										<!-- Day Selection -->
										<div class="space-y-2">
											<label class="text-sm font-medium">Day of Week</label>
											<Select v-model="editForm.day_of_week">
												<SelectTrigger>
													<SelectValue :placeholder="editForm.day_of_week" />
												</SelectTrigger>
												<SelectContent>
													<SelectItem v-for="day in days" :key="day" :value="day">
														{{ day }}
													</SelectItem>
												</SelectContent>
											</Select>
										</div>

										<!-- Time Range -->
										<div class="grid gap-4">
											<!-- Start Time -->
											<div class="space-y-2">
												<label class="text-sm font-medium">From</label>
												<div class="grid grid-cols-2 gap-2">
													<Select v-model="editingStartHour">
														<SelectTrigger>
															<SelectValue placeholder="Hour" />
														</SelectTrigger>
														<SelectContent>
															<SelectItem
																v-for="hour in hours"
																:key="hour.value"
																:value="hour.value"
															>
																{{ hour.label }}
															</SelectItem>
														</SelectContent>
													</Select>

													<Select v-model="editingStartMinute">
														<SelectTrigger>
															<SelectValue placeholder="Min" />
														</SelectTrigger>
														<SelectContent>
															<SelectItem v-for="min in minutes" :key="min" :value="min">
																{{ min }}
															</SelectItem>
														</SelectContent>
													</Select>
												</div>
											</div>

											<!-- End Time -->
											<div class="space-y-2">
												<label class="text-sm font-medium">To</label>
												<div class="grid grid-cols-2 gap-2">
													<Select v-model="editingEndHour">
														<SelectTrigger>
															<SelectValue placeholder="Hour" />
														</SelectTrigger>
														<SelectContent>
															<SelectItem
																v-for="hour in hours"
																:key="hour.value"
																:value="hour.value"
															>
																{{ hour.label }}
															</SelectItem>
														</SelectContent>
													</Select>

													<Select v-model="editingEndMinute">
														<SelectTrigger>
															<SelectValue placeholder="Min" />
														</SelectTrigger>
														<SelectContent>
															<SelectItem v-for="min in minutes" :key="min" :value="min">
																{{ min }}
															</SelectItem>
														</SelectContent>
													</Select>
												</div>
											</div>
										</div>

										<p v-if="editTimeError" class="text-destructive text-sm mt-1">
											{{ editTimeError }}
										</p>

										<div class="flex gap-2">
											<Button
												@click="handleUpdate"
												:disabled="!isEditFormValid || editForm.processing"
											>
												Save
											</Button>
											<Button variant="outline" @click="cancelEditing">Cancel</Button>
										</div>
									</div>
								</div>
								<div v-else class="flex items-center justify-between">
									<div class="space-y-1">
										<div class="flex items-baseline gap-2">
											<p class="text-sm font-medium">{{ schedule.day_of_week }}</p>
											<p class="text-xs text-muted-foreground">
												{{
													format(getNextOccurrence(schedule.day_of_week), "MMM d, yyyy")
												}}
											</p>
										</div>
										<p class="text-sm text-muted-foreground">
											{{ formatScheduleTime(schedule) }}
										</p>
									</div>
									<div class="flex gap-2">
										<Button size="sm" variant="outline" @click="startEditing(schedule)">
											Edit
										</Button>
										<Button
											size="sm"
											variant="destructive"
											:disabled="deleteForm.processing"
											@click="initiateDelete(schedule)"
										>
											Delete
										</Button>
									</div>
								</div>
							</div>
						</div>

						<!-- Next Week's Schedules -->
						<div v-if="nextWeekSchedules.length" class="space-y-3">
							<h3 class="text-sm font-medium text-muted-foreground">Next Week</h3>
							<div
								v-for="schedule in nextWeekSchedules"
								:key="schedule.id"
								class="p-4 border rounded-lg hover:bg-muted/50 transition-colors"
							>
								<!-- Same content as This Week's schedules -->
								<div v-if="editingSchedule?.id === schedule.id" class="space-y-4">
									<div class="space-y-4">
										<!-- Day Selection -->
										<div class="space-y-2">
											<label class="text-sm font-medium">Day of Week</label>
											<Select v-model="editForm.day_of_week">
												<SelectTrigger>
													<SelectValue :placeholder="editForm.day_of_week" />
												</SelectTrigger>
												<SelectContent>
													<SelectItem v-for="day in days" :key="day" :value="day">
														{{ day }}
													</SelectItem>
												</SelectContent>
											</Select>
										</div>

										<!-- Time Range -->
										<div class="grid gap-4">
											<!-- Start Time -->
											<div class="space-y-2">
												<label class="text-sm font-medium">From</label>
												<div class="grid grid-cols-2 gap-2">
													<Select v-model="editingStartHour">
														<SelectTrigger>
															<SelectValue placeholder="Hour" />
														</SelectTrigger>
														<SelectContent>
															<SelectItem
																v-for="hour in hours"
																:key="hour.value"
																:value="hour.value"
															>
																{{ hour.label }}
															</SelectItem>
														</SelectContent>
													</Select>

													<Select v-model="editingStartMinute">
														<SelectTrigger>
															<SelectValue placeholder="Min" />
														</SelectTrigger>
														<SelectContent>
															<SelectItem v-for="min in minutes" :key="min" :value="min">
																{{ min }}
															</SelectItem>
														</SelectContent>
													</Select>
												</div>
											</div>

											<!-- End Time -->
											<div class="space-y-2">
												<label class="text-sm font-medium">To</label>
												<div class="grid grid-cols-2 gap-2">
													<Select v-model="editingEndHour">
														<SelectTrigger>
															<SelectValue placeholder="Hour" />
														</SelectTrigger>
														<SelectContent>
															<SelectItem
																v-for="hour in hours"
																:key="hour.value"
																:value="hour.value"
															>
																{{ hour.label }}
															</SelectItem>
														</SelectContent>
													</Select>

													<Select v-model="editingEndMinute">
														<SelectTrigger>
															<SelectValue placeholder="Min" />
														</SelectTrigger>
														<SelectContent>
															<SelectItem v-for="min in minutes" :key="min" :value="min">
																{{ min }}
															</SelectItem>
														</SelectContent>
													</Select>
												</div>
											</div>
										</div>

										<p v-if="editTimeError" class="text-destructive text-sm mt-1">
											{{ editTimeError }}
										</p>

										<div class="flex gap-2">
											<Button
												@click="handleUpdate"
												:disabled="!isEditFormValid || editForm.processing"
											>
												Save
											</Button>
											<Button variant="outline" @click="cancelEditing">Cancel</Button>
										</div>
									</div>
								</div>
								<div v-else class="flex items-center justify-between">
									<div class="space-y-1">
										<div class="flex items-baseline gap-2">
											<p class="text-sm font-medium">{{ schedule.day_of_week }}</p>
											<p class="text-xs text-muted-foreground">
												{{
													format(getNextOccurrence(schedule.day_of_week), "MMM d, yyyy")
												}}
											</p>
										</div>
										<p class="text-sm text-muted-foreground">
											{{ formatScheduleTime(schedule) }}
										</p>
									</div>
									<div class="flex gap-2">
										<Button size="sm" variant="outline" @click="startEditing(schedule)">
											Edit
										</Button>
										<Button
											size="sm"
											variant="destructive"
											:disabled="deleteForm.processing"
											@click="initiateDelete(schedule)"
										>
											Delete
										</Button>
									</div>
								</div>
							</div>
						</div>

						<!-- No schedules message -->
						<p
							v-if="!currentWeekSchedules.length && !nextWeekSchedules.length"
							class="text-muted-foreground py-8 text-center text-sm"
						>
							No schedules set yet. Add your availability using the form.
						</p>
					</div>
				</CardContent>
			</Card>
		</div>
	</div>

	<ConfirmDialog
		:show="showDeleteDialog"
		title="Delete Schedule"
		description="Are you sure you want to delete this schedule? This action cannot be undone and will remove this time slot from your availability."
		confirmLabel="Delete Schedule"
		confirmVariant="destructive"
		:processing="deleteForm.processing"
		@confirm="handleDelete"
		@update:show="showDeleteDialog = $event"
		@cancel="showDeleteDialog = false"
	/>
</template>
