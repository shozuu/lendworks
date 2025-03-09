<script setup>
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import { formatDateTime } from "@/lib/formatters";
import { format } from "date-fns";
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
  rental: Object,
  userRole: String,
  lenderSchedules: Array,
});

const initiateForm = useForm({});
const scheduleForm = useForm({
  return_datetime: '',
  start_time: '',
  end_time: ''
});
const confirmForm = useForm({});

// Dialog state
const showEarlyReturnDialog = ref(false);

// Handle return initiation
const handleInitiateReturn = () => {
  const today = new Date();
  const endDate = new Date(props.rental.end_date);
  
  today.setHours(0, 0, 0, 0);
  endDate.setHours(0, 0, 0, 0);
  
  if (today < endDate) {
    showEarlyReturnDialog.value = true;
  } else {
    proceedWithReturn();
  }
};

const proceedWithReturn = () => {
  initiateForm.post(route('rentals.initiate-return', props.rental.id), {
    preserveScroll: true,
    onSuccess: () => {
      showEarlyReturnDialog.value = false;
    }
  });
};

const handleDialogCancel = () => {
  showEarlyReturnDialog.value = false;
};

// Available schedules computation
const availableSchedules = computed(() => {
  if (!props.rental.end_date || !props.lenderSchedules?.length) return [];
  
  const endDate = new Date(props.rental.end_date);
  endDate.setHours(0, 0, 0, 0);

  return props.lenderSchedules
    .map(schedule => ({
      ...schedule,
      scheduleDate: getScheduleDate(schedule.day_of_week),
      formattedTime: formatScheduleTime(schedule)
    }))
    .filter(schedule => schedule.scheduleDate >= endDate)
    .sort((a, b) => a.scheduleDate - b.scheduleDate);
});

// Helper functions for date/time formatting
const getScheduleDate = (dayOfWeek) => {
  const today = new Date();
  const daysMap = {
    'Monday': 1, 'Tuesday': 2, 'Wednesday': 3,
    'Thursday': 4, 'Friday': 5, 'Saturday': 6, 'Sunday': 0
  };
  
  let daysToAdd = daysMap[dayOfWeek] - today.getDay();
  if (daysToAdd <= 0) daysToAdd += 7;
  
  const scheduleDate = new Date(today);
  scheduleDate.setDate(today.getDate() + daysToAdd);
  return scheduleDate;
};

const formatScheduleTime = (schedule) => {
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

  return `${formatTimeString(schedule.start_time)} to ${formatTimeString(schedule.end_time)}`;
};

// Handle schedule selection
const handleScheduleSubmit = (schedule) => {
  const datetime = format(schedule.scheduleDate, 'yyyy-MM-dd');
  
  console.log('Submitting schedule:', {
    return_datetime: datetime,
    start_time: schedule.start_time,
    end_time: schedule.end_time
  });

  // Reset form data before submitting
  scheduleForm.clearErrors();
  scheduleForm.return_datetime = datetime;
  scheduleForm.start_time = schedule.start_time;
  scheduleForm.end_time = schedule.end_time;

  scheduleForm.post(route('return-schedules.store', props.rental.id), {
    preserveScroll: true,
    onError: (errors) => {
      console.error('Submit failed:', errors);
    },
    onSuccess: () => {
      console.log('Submit successful');
      // Reset form after successful submission
      scheduleForm.reset();
    }
  });
};

// Handle schedule confirmation by lender
const handleConfirmSchedule = () => {
  if (!selectedSchedule.value) return;
  
  confirmForm.patch(route('return-schedules.confirm', {
    rental: props.rental.id
  }), {
    preserveScroll: true
  });
};

// Computed properties for visibility control
const showSchedulePicker = computed(() => {
  return props.rental.status === 'pending_return' && 
         props.userRole === 'renter' && 
         !props.rental.return_schedules?.some(s => s.is_selected);
});

const selectedSchedule = computed(() => {
  if (!props.rental.return_schedules?.length) return null;
  return props.rental.return_schedules.find(s => s.is_selected);
});

const confirmedSchedule = computed(() => {
  if (!props.rental.return_schedules?.length) return null;
  return props.rental.return_schedules.find(s => s.is_confirmed);
});
</script>

<template>
  <Card v-if="rental.status === 'active' || rental.status === 'pending_return'">
    <CardHeader>
      <CardTitle>Return Process</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="space-y-4">
        <!-- Initiate Return Button -->
        <div v-if="rental.status === 'active' && userRole === 'renter'">
          <Button 
            class="w-full" 
            @click="handleInitiateReturn"
            :disabled="initiateForm.processing"
          >
            Initiate Return Process
          </Button>
        </div>

        <!-- Schedule Selection for Renter -->
        <div v-if="showSchedulePicker" class="space-y-4">
          <div 
            v-for="schedule in availableSchedules" 
            :key="schedule.id"
            class="p-4 border rounded-lg hover:bg-muted/50 transition-colors"
          >
            <div class="flex items-center justify-between">
              <div class="space-y-1">
                <div class="flex items-baseline gap-2">
                  <p class="text-sm font-medium">{{ schedule.day_of_week }}</p>
                  <p class="text-xs text-muted-foreground">
                    {{ format(schedule.scheduleDate, 'MMM d, yyyy') }}
                  </p>
                </div>
                <p class="text-sm text-muted-foreground">
                  {{ schedule.formattedTime }}
                </p>
              </div>
              <Button
                size="sm"
                @click="handleScheduleSubmit(schedule)"
                :disabled="scheduleForm.processing"
              >
                Select
              </Button>
            </div>
          </div>
        </div>

        <!-- Selected Schedule Display -->
        <div v-if="selectedSchedule && !selectedSchedule.is_confirmed" 
            class="space-y-4"
        >
          <div class="text-center">
            <h3 class="font-medium">Selected Return Schedule</h3>
          </div>
          
          <div class="p-4 border rounded-lg bg-muted/30">
            <div class="space-y-3">
              <div class="flex items-baseline justify-between">
                <h4 class="font-medium">{{ format(new Date(selectedSchedule.return_datetime), 'EEEE') }}</h4>
                <span class="text-sm">{{ format(new Date(selectedSchedule.return_datetime), 'MMMM d, yyyy') }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">Time Frame</span>
                <span class="font-medium">{{ formatScheduleTime(selectedSchedule) }}</span>
              </div>
              
              <div class="pt-2 mt-2 border-t text-xs text-muted-foreground text-center italic">
                Schedule selected on {{ format(new Date(selectedSchedule.created_at), 'MMM d, yyyy h:mm a') }}
              </div>

              <!-- Lender Confirmation Button -->
              <div v-if="userRole === 'lender'" class="pt-2 border-t">
                <Button 
                  class="w-full"
                  @click="handleConfirmSchedule"
                  :disabled="confirmForm.processing"
                >
                  Confirm Return Schedule
                </Button>
              </div>

              <!-- Renter Waiting Message -->
              <div v-else class="pt-2 border-t text-center text-sm text-muted-foreground">
                Waiting for owner to confirm schedule...
              </div>
            </div>
          </div>
        </div>

        <!-- Confirmed Schedule Display -->
        <div v-if="confirmedSchedule" class="p-4 border rounded-lg bg-muted/50">
          <div class="space-y-2">
            <h3 class="font-medium">Confirmed Return Schedule</h3>
            <p class="text-sm">{{ formatDateTime(confirmedSchedule.return_datetime) }}</p>
            <p class="text-sm text-muted-foreground">
              {{ formatScheduleTime(confirmedSchedule) }}
            </p>
          </div>
        </div>

        <!-- Waiting Message for Lender -->
        <div 
          v-if="rental.status === 'pending_return' && userRole === 'lender' && !selectedSchedule" 
          class="p-4 text-center text-muted-foreground bg-muted/30 rounded-lg"
        >
          Waiting for renter to select a return schedule...
        </div>
      </div>
    </CardContent>
  </Card>

  <!-- Early Return Confirmation Dialog -->
  <ConfirmDialog
    v-model:show="showEarlyReturnDialog"
    title="Early Return Notice"
    description="Are you sure you want to return this item before the rental period ends?"
    confirmLabel="Yes, Proceed with Return"
    cancelLabel="No, Keep Renting"
    :processing="initiateForm.processing"
    @confirm="proceedWithReturn"
    @cancel="handleDialogCancel"
  >
    <div class="mt-6 space-y-4">
      <div class="p-4 bg-muted/30 rounded-lg space-y-3">
        <h4 class="font-medium text-sm">Important Notes:</h4>
        <div class="space-y-2">
          <div class="flex items-start gap-2">
            <div class="h-5 w-5 flex items-center justify-center">
              <span class="text-primary text-lg">•</span>
            </div>
            <p class="text-sm text-muted-foreground">
              No refund will be issued for any remaining days in the rental period
            </p>
          </div>
          <div class="flex items-start gap-2">
            <div class="h-5 w-5 flex items-center justify-center">
              <span class="text-primary text-lg">•</span>
            </div>
            <p class="text-sm text-muted-foreground">
              Return of security deposit is contingent upon verification of the item's condition
            </p>
          </div>
        </div>
      </div>
    </div>
  </ConfirmDialog>
</template>
