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
  'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
];

const hours = Array.from({ length: 24 }, (_, i) => ({
  value: i.toString().padStart(2, '0'),
  label: new Date(0, 0, 0, i).toLocaleString('en-US', { hour: 'numeric', hour12: true })
}));

const minutes = ['00', '15', '30', '45'];

// Form state
const selectedDay = ref('');
const startHour = ref('');
const startMinute = ref('');
const endHour = ref('');
const endMinute = ref('');

const form = useForm({
  day_of_week: '',
  start_time: '',
  end_time: '',
});

const deleteForm = useForm({});

// Computed time values
watch([startHour, startMinute], () => {
  if (startHour.value && startMinute.value) {
    form.start_time = `${startHour.value}:${startMinute.value}`;
  }
});

watch([endHour, endMinute], () => {
  if (endHour.value && endMinute.value) {
    form.end_time = `${endHour.value}:${endMinute.value}`;
  }
});

watch(selectedDay, (value) => {
  form.day_of_week = value;
});

// Add error state
const timeError = ref('');

// Update the time validation
const validateTimeRange = () => {
  timeError.value = '';

  if (!startHour.value || !startMinute.value || !endHour.value || !endMinute.value) {
    return false;
  }

  const start = parseInt(startHour.value) * 60 + parseInt(startMinute.value);
  const end = parseInt(endHour.value) * 60 + parseInt(endMinute.value);

  if (end <= start) {
    timeError.value = 'End time must be later than start time and within the same day';
    return false;
  }

  return true;
};

// Update watches for time inputs
watch([startHour, startMinute, endHour, endMinute], () => {
  if (startHour.value && startMinute.value && endHour.value && endMinute.value) {
    validateTimeRange();
  }
});

const handleSubmit = () => {
  if (!validateTimeRange()) {
    return;
  }

  form.post(route('lender.pickup-schedules.store'), {
    preserveScroll: true,
    onSuccess: () => {
      // Reset form
      selectedDay.value = '';
      startHour.value = '';
      startMinute.value = '';
      endHour.value = '';
      endMinute.value = '';
      timeError.value = '';
    },
  });
};

const scheduleToDelete = ref(null);
const showDeleteDialog = ref(false);

const initiateDelete = (schedule) => {
  scheduleToDelete.value = schedule;
  showDeleteDialog.value = true;
};

const handleDelete = () => {
  if (!scheduleToDelete.value) return;
  
  deleteForm.delete(route('lender.pickup-schedules.destroy', scheduleToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteDialog.value = false;
      scheduleToDelete.value = null;
    },
  });
};

// Add a day order map for sorting
const dayOrder = {
  'Monday': 1,
  'Tuesday': 2,
  'Wednesday': 3,
  'Thursday': 4,
  'Friday': 5,
  'Saturday': 6,
  'Sunday': 7
};

// Update the safeSchedules computed property to include sorting
const safeSchedules = computed(() => {
  const schedules = props.schedules || [];
  return [...schedules].sort((a, b) => {
    // Sort by day of week first
    const dayDiff = dayOrder[a.day_of_week] - dayOrder[b.day_of_week];
    if (dayDiff !== 0) return dayDiff;
    
    // If same day, sort by start time
    return a.start_time.localeCompare(b.start_time);
  });
});

const editingSchedule = ref(null);
const editForm = useForm({
  day_of_week: '',
  start_time: '',
  end_time: '',
});

const editingStartHour = ref('');
const editingStartMinute = ref('');
const editingEndHour = ref('');
const editingEndMinute = ref('');

const startEditing = (schedule) => {
  editingSchedule.value = schedule;
  
  // Parse existing times
  const [startHour, startMin] = schedule.start_time.split(':');
  const [endHour, endMin] = schedule.end_time.split(':');
  
  editingStartHour.value = startHour;
  editingStartMinute.value = startMin;
  editingEndHour.value = endHour;
  editingEndMinute.value = endMin;
  
  editForm.day_of_week = schedule.day_of_week;
  editForm.start_time = schedule.start_time;
  editForm.end_time = schedule.end_time;
};

// Update cancelEditing to clear error
const cancelEditing = () => {
  editingSchedule.value = null;
  editForm.reset();
  editingStartHour.value = '';
  editingStartMinute.value = '';
  editingEndHour.value = '';
  editingEndMinute.value = '';
  editTimeError.value = '';
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

// Add edit form error state
const editTimeError = ref('');

// Add validation for edit form
const validateEditTimeRange = () => {
  editTimeError.value = '';

  if (!editingStartHour.value || !editingStartMinute.value || !editingEndHour.value || !editingEndMinute.value) {
    return false;
  }

  const start = parseInt(editingStartHour.value) * 60 + parseInt(editingStartMinute.value);
  const end = parseInt(editingEndHour.value) * 60 + parseInt(editingEndMinute.value);

  if (end <= start) {
    editTimeError.value = 'End time must be later than start time and within the same day';
    return false;
  }

  return true;
};

// Add watch for edit form time inputs
watch([editingStartHour, editingStartMinute, editingEndHour, editingEndMinute], () => {
  if (editingStartHour.value && editingStartMinute.value && editingEndHour.value && editingEndMinute.value) {
    validateEditTimeRange();
  }
});

// Add computed property for edit form validity
const isEditFormValid = computed(() => {
  if (!editForm.day_of_week || !editingStartHour.value || !editingStartMinute.value || 
      !editingEndHour.value || !editingEndMinute.value) {
    return false;
  }

  const start = parseInt(editingStartHour.value) * 60 + parseInt(editingStartMinute.value);
  const end = parseInt(editingEndHour.value) * 60 + parseInt(editingEndMinute.value);

  return end > start && !editTimeError.value;
});

// Update handleUpdate to include validation
const handleUpdate = () => {
  if (!validateEditTimeRange()) {
    return;
  }

  editForm.patch(route('lender.pickup-schedules.update', editingSchedule.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      cancelEditing();
      editTimeError.value = '';
    },
  });
};

// Add this computed function to get the next occurrence of a day
const getNextOccurrence = (dayOfWeek) => {
  const today = new Date();
  const startOfCurrentWeek = startOfWeek(today, { weekStartsOn: 1 }); // Start from Monday
  const daysMap = {
    'Monday': 0,
    'Tuesday': 1,
    'Wednesday': 2,
    'Thursday': 3,
    'Friday': 4,
    'Saturday': 5,
    'Sunday': 6
  };
  
  const dayIndex = daysMap[dayOfWeek];
  const nextDate = addDays(startOfCurrentWeek, dayIndex);
  
  // If the day has already passed this week, add 7 days to get next week's date
  if (nextDate < today) {
    return addDays(nextDate, 7);
  }
  
  return nextDate;
};

// Add this function to format the schedule time
const formatScheduleTime = (schedule) => {
  const formatTimeString = (timeStr) => {
    const [hours, minutes] = timeStr.split(':');
    const date = new Date();
    date.setHours(parseInt(hours), parseInt(minutes));
    return date.toLocaleTimeString('en-US', { 
      hour: 'numeric',
      minute: '2-digit',
      hour12: true 
    });
  };

  return `${formatTimeString(schedule.start_time)} to ${formatTimeString(schedule.end_time)}`;
};

// Add a computed property for form validity
const isFormValid = computed(() => {
  if (!form.day_of_week || !startHour.value || !startMinute.value || !endHour.value || !endMinute.value) {
    return false;
  }

  const start = parseInt(startHour.value) * 60 + parseInt(startMinute.value);
  const end = parseInt(endHour.value) * 60 + parseInt(endMinute.value);

  return end > start && !timeError.value;
});
</script>

<template>
  <div class="space-y-4">
    <h2 class="text-lg font-semibold">Pickup Schedule Management</h2>
    <p class="text-muted-foreground text-sm">Set your regular availability for item handovers</p>
    
    <div class="grid gap-6 md:grid-cols-2">
      <!-- Add Schedule Form Card -->
      <Card class="shadow-sm">
        <CardHeader class="bg-card border-b">
          <CardTitle>Add Availability</CardTitle>
          <p class="text-muted-foreground text-sm">
            Set recurring time slots when you're available for item handovers
          </p>
        </CardHeader>
        <CardContent class="p-6">
          <div class="space-y-4">
            <!-- Day Selection -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Day of Week</label>
              <Select v-model="selectedDay">
                <SelectTrigger>
                  <SelectValue placeholder="Select day" />
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
                  <Select v-model="startHour">
                    <SelectTrigger>
                      <SelectValue placeholder="Hour" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="hour in hours" :key="hour.value" :value="hour.value">
                        {{ hour.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <Select v-model="startMinute">
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
                  <Select v-model="endHour">
                    <SelectTrigger>
                      <SelectValue placeholder="Hour" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="hour in hours" :key="hour.value" :value="hour.value">
                        {{ hour.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <Select v-model="endMinute">
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

            <p v-if="timeError" class="text-destructive text-sm mt-1">
              {{ timeError }}
            </p>

            <Button 
              class="w-full" 
              :disabled="!isFormValid || form.processing"
              @click="handleSubmit"
            >
              Add Schedule
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
          <div class="space-y-3">
            <div
              v-for="schedule in safeSchedules"
              :key="schedule.id"
              class="p-4 border rounded-lg hover:bg-muted/50 transition-colors"
            >
              <div v-if="editingSchedule?.id === schedule.id" class="space-y-4">
                <!-- Edit Form -->
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
                            <SelectItem v-for="hour in hours" :key="hour.value" :value="hour.value">
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
                            <SelectItem v-for="hour in hours" :key="hour.value" :value="hour.value">
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
                    <Button variant="outline" @click="cancelEditing">
                      Cancel
                    </Button>
                  </div>
                </div>
              </div>
              <div v-else class="flex items-center justify-between">
                <div class="space-y-1">
                  <div class="flex items-baseline gap-2">
                    <p class="text-sm font-medium">{{ schedule.day_of_week }}</p>
                    <p class="text-xs text-muted-foreground">
                      Next: {{ format(getNextOccurrence(schedule.day_of_week), 'MMM d, yyyy') }}
                    </p>
                  </div>
                  <p class="text-sm text-muted-foreground">
                    {{ formatScheduleTime(schedule) }}
                  </p>
                </div>
                <div class="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    @click="startEditing(schedule)"
                  >
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
            <p v-if="!safeSchedules.length" class="text-muted-foreground py-8 text-center text-sm">
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
