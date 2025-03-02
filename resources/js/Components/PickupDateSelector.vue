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

const handleSelectDate = (scheduleId) => {
  selectForm.patch(route("pickup-schedules.select", { 
    rental: props.rental.id,
    lender_schedule: scheduleId  // Update this line to match route parameter name
  }), {
    preserveScroll: true,
  });
};

// Get dates for the next 7 days
const nextWeekDates = computed(() => {
  const dates = [];
  for (let i = 0; i < 7; i++) {
    dates.push({
      date: addDays(new Date(), i),
      formattedDay: format(addDays(new Date(), i), 'EEEE'),
    });
  }
  return dates;
});

// Filter schedules based on day of week
const getAvailableSchedules = (dayOfWeek) => {
  return props.lenderSchedules.filter(schedule => schedule.day_of_week === dayOfWeek);
};

const selectedSchedule = computed(() => {
  if (!props.rental.pickup_schedules?.length) return null;
  return props.rental.pickup_schedules.find(s => s.is_selected);
});

// Update the formatScheduleTime function to handle both schedule types
const formatScheduleTime = (schedule) => {
  if (!schedule) return '';
  
  const formatTimeString = (timeStr) => {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    const date = new Date();
    date.setHours(parseInt(hours), parseInt(minutes));
    return date.toLocaleTimeString('en-US', { 
      hour: 'numeric',
      minute: '2-digit',
      hour12: true 
    });
  };

  // If it's a selected schedule, format the pickup_datetime
  if (schedule.pickup_datetime) {
    const date = new Date(schedule.pickup_datetime);
    return date.toLocaleTimeString('en-US', { 
      hour: 'numeric',
      minute: '2-digit',
      hour12: true 
    });
  }

  // If it's a regular schedule
  return `${formatTimeString(schedule.start_time)} to ${formatTimeString(schedule.end_time)}`;
};

// Add this computed property to get available times for each day
const availableTimesForDay = computed(() => (dayOfWeek) => {
  return props.lenderSchedules
    .filter(schedule => schedule.day_of_week === dayOfWeek)
    .map(schedule => ({
      ...schedule,
      formattedTime: formatScheduleTime(schedule)
    }));
});
</script>

<template>
  <Card class="shadow-sm">
    <CardHeader class="bg-card border-b">
      <CardTitle>Pickup Schedule</CardTitle>
    </CardHeader>
    <CardContent class="p-6">
      <!-- For Renter: Show available schedules -->
      <div v-if="userRole === 'renter' && !selectedSchedule" class="space-y-6">
        <div v-for="dateObj in nextWeekDates" :key="dateObj.date">
          <h3 class="font-medium mb-2">
            {{ format(dateObj.date, 'MMMM d, yyyy') }} ({{ dateObj.formattedDay }})
          </h3>
          <div class="space-y-2">
            <div 
              v-for="schedule in availableTimesForDay(dateObj.formattedDay)" 
              :key="schedule.id"
              class="flex items-center justify-between p-3 border rounded-lg"
            >
              <span>{{ schedule.formattedTime }}</span>
              <Button 
                size="sm" 
                @click="handleSelectDate(schedule.id)"
                :disabled="selectForm.processing"
              >
                Select
              </Button>
            </div>
            <p 
              v-if="!availableTimesForDay(dateObj.formattedDay).length" 
              class="text-muted-foreground text-sm text-center py-2"
            >
              No available schedules
            </p>
          </div>
        </div>
      </div>

      <!-- For both: Show selected schedule -->
      <div v-if="selectedSchedule" class="text-center space-y-2">
        <h3 class="font-medium">Selected Pickup Schedule</h3>
        <p>{{ format(new Date(selectedSchedule.pickup_datetime), "MMMM d, yyyy") }}</p>
        <p>{{ formatScheduleTime(selectedSchedule) }}</p>
      </div>

      <!-- For Lender: Show waiting message -->
      <div v-if="userRole === 'lender' && !selectedSchedule" class="text-center text-muted-foreground">
        Waiting for renter to select a pickup schedule...
      </div>
    </CardContent>
  </Card>
</template>
