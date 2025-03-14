<script setup>
import { ref, computed, watch } from "vue";
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
import { formatDateTime } from "@/lib/formatters";
import { format, isValid, isBefore, startOfDay } from "date-fns";

const props = defineProps({
  rental: Object,
  userRole: String,
});

// Date input refs
const month = ref("");
const day = ref("");
const year = ref("");
const hour = ref("");
const minute = ref("");
const isPM = ref(false);

const errors = ref({
  date: '',
  time: ''
});

// Generate options for dropdowns
const months = Array.from({ length: 12 }, (_, i) => ({
  value: (i + 1).toString().padStart(2, "0"),
  label: new Date(0, i).toLocaleString("default", { month: "long" }),
}));

const days = Array.from({ length: 31 }, (_, i) => ({
  value: (i + 1).toString().padStart(2, "0"),
  label: (i + 1).toString(),
}));

const years = Array.from({ length: 2 }, (_, i) => ({
  value: (new Date().getFullYear() + i).toString(),
  label: (new Date().getFullYear() + i).toString(),
}));

const hours = Array.from({ length: 12 }, (_, i) => ({
  value: (i + 1).toString().padStart(2, "0"),
  label: (i + 1).toString(),
}));

const minutes = Array.from({ length: 12 }, (_, i) => ({
  value: (i * 5).toString().padStart(2, "0"),
  label: (i * 5).toString().padStart(2, "0"),
}));

// Add periods array for AM/PM select
const periods = [
  { value: false, label: "AM" },
  { value: true, label: "PM" },
];

const scheduleForm = useForm({
  pickup_datetime: "",
});

const deleteForm = useForm({});
const selectForm = useForm({});

const isLender = computed(() => props.userRole === "lender");
const isRenter = computed(() => props.userRole === "renter");

const availableDays = computed(() => {
  if (!month.value || !year.value) return [];
  
  const daysInMonth = new Date(
    parseInt(year.value),
    parseInt(month.value),
    0
  ).getDate();

  return Array.from({ length: daysInMonth }, (_, i) => ({
    value: (i + 1).toString().padStart(2, "0"),
    label: (i + 1).toString(),
  }));
});

watch([month, year], () => {
  if (day.value) {
    const maxDays = availableDays.value.length;
    if (parseInt(day.value) > maxDays) {
      day.value = "";
    }
  }
});

// Add a watch effect for all inputs
watch([month, day, year, hour, minute, isPM], () => {
  if (month.value || day.value || year.value || hour.value || minute.value) {
    validateDateTime();
  }
}, { deep: true });

const validateDateTime = () => {
  errors.value = { date: '', time: '' };

  // Skip validation if any required field is empty
  if (!month.value || !day.value || !year.value || !hour.value || !minute.value) {
    return false;
  }

  const selectedDate = new Date(
    parseInt(year.value),
    parseInt(month.value) - 1,
    parseInt(day.value)
  );

  if (!isValid(selectedDate)) {
    errors.value.date = 'Invalid date selected';
    return false;
  }

  if (isBefore(startOfDay(selectedDate), startOfDay(new Date()))) {
    errors.value.date = 'Cannot select a past date';
    return false;
  }

  return true;
};

const handleAddSchedule = () => {
  if (!validateDateTime()) {
    return;
  }

  // Convert to ISO datetime
  const dateStr = `${year.value}-${month.value}-${day.value}`;
  let timeStr = `${hour.value}:${minute.value}`;
  
  // Adjust hour for PM
  if (isPM.value && hour.value !== "12") {
    timeStr = `${(parseInt(hour.value) + 12).toString()}:${minute.value}`;
  }
  // Adjust hour for AM
  if (!isPM.value && hour.value === "12") {
    timeStr = `00:${minute.value}`;
  }

  scheduleForm.pickup_datetime = `${dateStr} ${timeStr}:00`;
  
  scheduleForm.post(route("pickup-schedules.store", { rental: props.rental.id }), {
    preserveScroll: true,
    onSuccess: () => {
      // Reset form
      month.value = "";
      day.value = "";
      year.value = "";
      hour.value = "";
      minute.value = "";
      isPM.value = false;
    },
  });
};

const handleDeleteSchedule = (id) => {
  deleteForm.delete(route("pickup-schedules.destroy", { 
    rental: props.rental.id,
    schedule: id 
  }), {
    preserveScroll: true,
  });
};

const handleSelectSchedule = (id) => {
  selectForm.patch(route("pickup-schedules.select", { 
    rental: props.rental.id,
    schedule: id 
  }), {
    preserveScroll: true,
  });
};

// Update isValidDateTime computed to use validateDateTime
const isValidDateTime = computed(() => {
  if (!month.value || !day.value || !year.value || !hour.value || !minute.value) {
    return false;
  }
  return validateDateTime();
});
</script>

<template>
  <div class="grid gap-8 md:grid-cols-2">
    <!-- Schedule Form -->
    <Card v-if="isLender" class="shadow-sm">
      <CardHeader class="bg-card border-b">
        <CardTitle>Add Pickup Schedule</CardTitle>
      </CardHeader>
      <CardContent class="p-6">
        <div class="space-y-4">
          <!-- Date Inputs -->
          <div class="space-y-2">
            <label class="text-sm font-medium">Date</label>
            <div class="grid grid-cols-3 gap-2">
              <Select v-model="month">
                <SelectTrigger>
                  <SelectValue placeholder="Month" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="m in months" :key="m.value" :value="m.value">
                    {{ m.label }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <Select v-model="day">
                <SelectTrigger>
                  <SelectValue placeholder="Day" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem 
                    v-for="d in availableDays" 
                    :key="d.value" 
                    :value="d.value"
                  >
                    {{ d.label }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <Select v-model="year">
                <SelectTrigger>
                  <SelectValue placeholder="Year" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="y in years" :key="y.value" :value="y.value">
                    {{ y.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <p v-if="errors.date" class="text-destructive text-sm mt-1">
              {{ errors.date }}
            </p>
          </div>

          <!-- Time Inputs -->
          <div class="space-y-2">
            <label class="text-sm font-medium">Time</label>
            <div class="grid grid-cols-3 items-center gap-2">
              <Select v-model="hour">
                <SelectTrigger>
                  <SelectValue placeholder="Hour" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="h in hours" :key="h.value" :value="h.value">
                    {{ h.label }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <Select v-model="minute">
                <SelectTrigger>
                  <SelectValue placeholder="Minute" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="m in minutes" :key="m.value" :value="m.value">
                    {{ m.label }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <!-- Replace RadioGroup with Select -->
              <Select v-model="isPM">
                <SelectTrigger>
                  <SelectValue placeholder="AM/PM" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem 
                    v-for="period in periods" 
                    :key="period.value" 
                    :value="period.value"
                  >
                    {{ period.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <p v-if="errors.time" class="text-destructive text-sm mt-1">
              {{ errors.time }}
            </p>
          </div>

          <Button
            class="w-full"
            :disabled="!isValidDateTime || scheduleForm.processing"
            @click="handleAddSchedule"
          >
            Add Schedule
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Schedule List -->
    <Card class="shadow-sm">
      <CardHeader class="bg-card border-b">
        <CardTitle>Pickup Schedules</CardTitle>
      </CardHeader>
      <CardContent class="p-6">
        <div class="space-y-4">
          <template v-if="rental.pickup_schedules?.length">
            <div
              v-for="schedule in rental.pickup_schedules"
              :key="schedule.id"
              class="flex items-center justify-between rounded-lg border p-4"
              :class="{ 'bg-muted': schedule.is_selected }"
            >
              <div>
                <p class="font-medium">
                  {{ formatDateTime(schedule.pickup_datetime, "MMMM D, YYYY") }}
                </p>
                <p class="text-muted-foreground text-sm">
                  {{ formatDateTime(schedule.pickup_datetime, "h:mm A") }}
                </p>
                <p v-if="schedule.is_selected" class="text-primary mt-1 text-sm">
                  Selected Schedule
                </p>
              </div>

              <div class="flex gap-2">
                <Button
                  v-if="isRenter && !schedule.is_selected"
                  size="sm"
                  @click="handleSelectSchedule(schedule.id)"
                  :disabled="selectForm.processing"
                >
                  Select
                </Button>
                <Button
                  v-if="isLender && !schedule.is_selected"
                  size="sm"
                  variant="destructive"
                  @click="handleDeleteSchedule(schedule.id)"
                  :disabled="deleteForm.processing"
                >
                  Delete
                </Button>
              </div>
            </div>
          </template>
          <p v-else class="text-muted-foreground text-center text-sm">
            No pickup schedules available.
          </p>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
