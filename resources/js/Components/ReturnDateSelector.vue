<script setup>
import { computed } from "vue";
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
  selectForm.patch(
    route("return-schedules.select", {
      rental: props.rental.id,
      lender_schedule: slot.id,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        emit("schedule-selected");
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

const availableTimeSlots = computed(() => {
  if (!props.lenderSchedules?.length) return [];
  
  return props.lenderSchedules
    .filter(schedule => schedule.is_active)
    .map(schedule => ({
      ...schedule,
      formattedTime: formatScheduleTime(schedule)
    }))
    .sort((a, b) => a.start_time.localeCompare(b.start_time));
});

// Add new computed property for the return date context
const returnDateContext = computed(() => {
  if (props.rental.is_overdue) {
    return format(new Date(), 'MMMM d, yyyy'); // Current date for overdue rentals
  }
  
  if (props.rental.status === 'pending_return') {
    // If return was initiated early, show initiation date
    const timelineEvent = props.rental.timeline_events.find(e => e.event_type === 'return_initiated');
    if (timelineEvent) {
      return format(new Date(timelineEvent.created_at), 'MMMM d, yyyy');
    }
  }
  
  // Default to rental end date
  return format(new Date(props.rental.end_date), 'MMMM d, yyyy');
});

// Add new computed property for context message
const contextMessage = computed(() => {
  if (props.rental.is_overdue) {
    return 'Select a return time for today';
  }
  
  if (props.rental.status === 'pending_return') {
    const timelineEvent = props.rental.timeline_events.find(e => e.event_type === 'return_initiated');
    if (timelineEvent) {
      return 'Select a return time for the initiated return date';
    }
  }
  
  return 'Select a return time for the rental end date';
});
</script>

<template>
  <Card class="shadow-sm">
    <CardHeader class="bg-card border-b">
      <CardTitle>Return Schedule</CardTitle>
      <p class="text-muted-foreground text-sm">
        {{ contextMessage }}
      </p>
      <p class="text-sm font-medium mt-1">{{ returnDateContext }}</p>
    </CardHeader>
    <CardContent class="p-0">
      <div class="px-6 py-4">
        <div v-if="availableTimeSlots.length" class="space-y-4">
          <div class="bg-muted/50 p-3 rounded-lg">
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
          No available time slots.
        </p>
      </div>
    </CardContent>
  </Card>
</template>
