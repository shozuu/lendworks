<script setup>
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import { formatDateTime, formatNumber } from "@/lib/formatters"; // Add formatNumber import
import { addDays, format } from "date-fns";
import ReturnProofDialog from '@/Components/ReturnProofDialog.vue';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Alert, AlertDescription } from "@/components/ui/alert"; // Add Alert components
import PayOverdueDialog from "@/Components/PayOverdueDialog.vue";

const props = defineProps({
  rental: Object,
  userRole: String,
  lenderSchedules: Array,  // Add this
});

const initiateForm = useForm({});
const scheduleForm = useForm({
  return_datetime: '',
  start_time: '',
  end_time: ''
}); // Remove the nested 'data' structure
const selectForm = useForm({});
const confirmForm = useForm({});

const handleInitiateReturn = () => {
  initiateForm.post(route('rentals.initiate-return', props.rental.id), {
    preserveScroll: true,
  });
};

const handleSelectSchedule = (schedule) => {
  selectForm.patch(route('return-schedules.select', { 
    rental: props.rental.id,
    schedule: schedule.id
  }), {
    preserveScroll: true,
  });
};

const handleConfirmSchedule = (schedule) => {
  confirmForm.patch(route('return-schedules.confirm', {
    rental: props.rental.id,
    schedule: schedule.id
  }), {
    preserveScroll: true,
  });
};

const canInitiateReturn = computed(() => {
  return props.rental.status === 'active' && 
         props.userRole === 'renter';
});

const selectedSchedule = computed(() => {
  if (!props.rental.return_schedules?.length) return null;
  return props.rental.return_schedules.find(s => s.is_selected);
});

const confirmedSchedule = computed(() => {
  if (!props.rental.return_schedules?.length) return null;
  return props.rental.return_schedules.find(s => s.is_confirmed);
});

const showReturnProofDialog = ref(false);
const returnProofType = ref('submit');

const handleSubmitReturn = () => {
  returnProofType.value = 'submit';
  showReturnProofDialog.value = true;
};

const handleConfirmReturn = () => {
  returnProofType.value = 'confirm';
  showReturnProofDialog.value = true;
};

// Add state for return date selection
const selectedDate = ref('');
const selectedTime = ref('');

// Replace the availableDates computed with this
const availableSchedules = computed(() => {
  if (!props.rental.end_date || !props.lenderSchedules?.length) return [];
  
  const endDate = new Date(props.rental.end_date);
  endDate.setHours(0, 0, 0, 0);

  return props.lenderSchedules
    .map(schedule => {
      const scheduleDate = getScheduleDate(schedule.day_of_week);
      return {
        ...schedule,
        scheduleDate,
        formattedTime: formatScheduleTime(schedule)
      };
    })
    .filter(schedule => schedule.scheduleDate >= endDate)
    .sort((a, b) => a.scheduleDate - b.scheduleDate);
});

// Add helper functions
const getScheduleDate = (dayOfWeek) => {
  const today = new Date();
  const daysMap = {
    'Monday': 1,
    'Tuesday': 2,
    'Wednesday': 3,
    'Thursday': 4,
    'Friday': 5,
    'Saturday': 6,
    'Sunday': 0
  };
  
  const currentDay = today.getDay();
  const targetDay = daysMap[dayOfWeek];
  let daysToAdd = targetDay - currentDay;
  
  if (daysToAdd <= 0) {
    daysToAdd += 7;
  }
  
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

const formatTimeFrame = (schedule) => {
  const formatTimeStr = (timeStr) => {
    const [hours, minutes] = timeStr.split(':');
    const date = new Date();
    date.setHours(parseInt(hours), parseInt(minutes));
    return date.toLocaleTimeString('en-US', { 
      hour: 'numeric',
      minute: '2-digit',
      hour12: true 
    });
  };

  return `${formatTimeStr(schedule.start_time)} to ${formatTimeStr(schedule.end_time)}`;
};

// Update the selectedScheduleDetails computed
const selectedScheduleDetails = computed(() => {
  if (!selectedSchedule.value) return null;
  
  const scheduleDate = new Date(selectedSchedule.value.return_datetime);
  
  return {
    dayOfWeek: format(scheduleDate, 'EEEE'),
    date: format(scheduleDate, 'MMMM d, yyyy'),
    timeFrame: formatTimeFrame(selectedSchedule.value),
    selectedOn: format(new Date(selectedSchedule.value.created_at), 'MMM d, yyyy h:mm a')
  };
});

// Update handleScheduleSubmit to properly send the data
const handleScheduleSubmit = (schedule) => {
  console.log('=== Return Schedule Submission Started ===');
  console.log('Schedule data:', schedule);
  
  const datetime = format(schedule.scheduleDate, 'yyyy-MM-dd');
  console.log('Formatted datetime:', datetime);
  
  // Update the form data directly, not through a nested 'data' object
  scheduleForm.return_datetime = datetime;
  scheduleForm.start_time = schedule.start_time;
  scheduleForm.end_time = schedule.end_time;
  
  console.log('Form data being sent:', scheduleForm);

  scheduleForm.post(route('return-schedules.store', props.rental.id), {
    preserveScroll: true,
    onError: (errors) => {
      console.error('Schedule submission failed with errors:', errors);
    },
    onSuccess: (response) => {
      console.log('Schedule submission successful!');
      window.location.reload();
    }
  });
};

// Add computed property to control schedule picker visibility
const showSchedulePicker = computed(() => {
  return props.rental.status === 'pending_return' && props.userRole === 'renter';
});

const showWaitingMessage = computed(() => {
  return props.rental.status === 'pending_return' && props.userRole === 'lender';
});

// Add ref for overdue payment dialog
const showOverduePayment = ref(false);

// Add isOverdue computed property
const isOverdue = computed(() => {
  if (props.rental.status !== 'active' || !props.rental.end_date) {
    return false;
  }
  return new Date(props.rental.end_date) < new Date();
});

// Fix references to rental in computed properties
const hasPendingOverduePayment = computed(() => {
  return isOverdue.value &&
    props.rental.payment_request?.type === 'overdue' &&
    props.rental.payment_request?.status === 'pending';
});

const hasVerifiedOverduePayment = computed(() => {
  return isOverdue.value &&
    props.rental.payment_request?.type === 'overdue' &&
    props.rental.payment_request?.status === 'verified';
});
</script>

<template>
  <Card v-if="rental.status === 'active' || rental.status === 'pending_return'">
    <CardHeader>
      <CardTitle>Return Process</CardTitle>
    </CardHeader>
    <CardContent>
      <!-- Show overdue states -->
      <div v-if="rental.is_overdue && rental.status === 'active'" class="space-y-4">
        <!-- Different views for renter -->
        <template v-if="userRole === 'renter'">
          <!-- Unpaid overdue state -->
          <template v-if="!hasPendingOverduePayment && !hasVerifiedOverduePayment">
            <Alert variant="destructive">
              <AlertDescription class="space-y-2">
                <p>This rental is overdue. Please pay the overdue fees to proceed with the return process.</p>
                <p class="font-medium">Overdue Fee: {{ formatNumber(rental.overdue_fee) }}</p>
              </AlertDescription>
            </Alert>
            
            <div class="flex gap-2">
              <Button 
                variant="default" 
                @click="showOverduePayment = true"
              >
                Pay Overdue Fees
              </Button>
              <Button 
                variant="outline" 
                disabled
              >
                Initiate Return
              </Button>
            </div>
          </template>

          <!-- Pending verification state -->
          <template v-else-if="hasPendingOverduePayment">
            <Alert variant="warning">
              <AlertDescription class="space-y-2">
                <p>Overdue payment submitted. Please wait for admin verification to proceed with return process.</p>
                <p class="font-medium">Overdue Fee: {{ formatNumber(rental.overdue_fee) }}</p>
              </AlertDescription>
            </Alert>
          </template>

          <!-- Payment verified state - show normal return process -->
          <template v-else-if="hasVerifiedOverduePayment">
            <div v-if="!rental.return_schedules?.length">
              <Button 
                class="w-full" 
                @click="handleInitiateReturn"
                :disabled="initiateForm.processing"
              >
                Initiate Return Process
              </Button>
            </div>
          </template>
        </template>

        <!-- Lender view for overdue states -->
        <template v-else>
          <Alert :variant="hasPendingOverduePayment ? 'warning' : hasVerifiedOverduePayment ? 'success' : 'warning'">
            <AlertDescription class="space-y-2">
              <template v-if="hasPendingOverduePayment">
                <p>Renter has submitted overdue payment. Waiting for admin verification.</p>
              </template>
              <template v-else-if="hasVerifiedOverduePayment">
                <p>Overdue payment has been verified. Waiting for renter to initiate return.</p>
              </template>
              <template v-else>
                <p>This rental is overdue. Waiting for the renter to pay the overdue fees.</p>
              </template>
              <p class="font-medium">Overdue Fee: {{ formatNumber(rental.overdue_fee) }}</p>
            </AlertDescription>
          </Alert>
        </template>
      </div>

      <!-- Show normal return process if not overdue or if overdue is paid -->
      <div v-else>
        <div class="space-y-4">
          <!-- Initiate Return Button -->
          <div v-if="canInitiateReturn && !rental.return_schedules?.length">
            <Button 
              class="w-full" 
              @click="handleInitiateReturn"
              :disabled="initiateForm.processing"
            >
              Initiate Return Process
            </Button>
          </div>

          <!-- Return Schedule Selection - Only visible to renter -->
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

            <p 
              v-if="!availableSchedules.length" 
              class="text-muted-foreground py-8 text-center text-sm"
            >
              No available schedules after the rental end date.
            </p>
          </div>

          <!-- Waiting message - Only visible to lender -->
          <div 
            v-if="showWaitingMessage" 
            class="p-4 text-center text-muted-foreground bg-muted/30 rounded-lg"
          >
            Waiting for renter to select a return schedule...
          </div>

          <!-- Selected Schedule Display -->
          <div v-if="selectedSchedule && !selectedSchedule.is_confirmed" 
              class="p-4 border rounded-lg"
          >
            <div class="space-y-3">
              <div class="space-y-1">
                <h3 class="font-medium">Proposed Return Schedule</h3>
                <div class="flex items-baseline justify-between">
                  <span class="text-sm">{{ selectedScheduleDetails.dayOfWeek }}</span>
                  <span class="text-sm text-muted-foreground">
                    {{ selectedScheduleDetails.date }}
                  </span>
                </div>
                <p class="text-sm text-muted-foreground">
                  {{ selectedScheduleDetails.timeFrame }}
                </p>
              </div>

              <div v-if="userRole === 'lender'" class="pt-2 border-t">
                <Button 
                  class="w-full"
                  @click="handleConfirmSchedule(selectedSchedule)"
                  :disabled="confirmForm.processing"
                >
                  Confirm Return Schedule
                </Button>
              </div>

              <div v-else class="pt-2 border-t text-center text-sm text-muted-foreground">
                Waiting for lender to confirm schedule...
              </div>
            </div>
          </div>

          <!-- Confirmed Schedule Display -->
          <div v-if="confirmedSchedule" class="p-4 border rounded-lg bg-muted/50">
            <div class="space-y-2">
              <h3 class="font-medium">Confirmed Return Schedule</h3>
              <p class="text-sm">{{ formatDateTime(confirmedSchedule.return_datetime) }}</p>
            </div>
          </div>

          <!-- Return Proof Actions -->
          <div v-if="rental.status === 'return_scheduled' && userRole === 'renter'">
            <Button 
              class="w-full" 
              @click="handleSubmitReturn"
            >
              Submit Return Proof
            </Button>
          </div>

          <div v-if="rental.status === 'pending_return_confirmation' && userRole === 'lender'">
            <Button 
              class="w-full" 
              @click="handleConfirmReturn"
            >
              Confirm Return Receipt
            </Button>
          </div>

          <!-- Return Proof Dialog -->
          <ReturnProofDialog
            v-model:show="showReturnProofDialog"
            :rental="rental"
            :type="returnProofType"
          />
        </div>
      </div>
    </CardContent>
  </Card>

  <!-- Only show payment dialog for renters and when payment is not pending -->
  <PayOverdueDialog
    v-if="userRole === 'renter' && !hasPendingOverduePayment"
    v-model:show="showOverduePayment"
    :rental="rental"
  />
</template>
