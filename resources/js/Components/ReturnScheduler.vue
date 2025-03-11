<script setup>
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { useForm } from "@inertiajs/vue3";
import { formatDateTime } from "@/lib/formatters";
import { format } from "date-fns";
import { formatNumber } from "@/lib/formatters";
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import PayOverdueDialog from '@/Components/PayOverdueDialog.vue';
import ReturnProofDialog from '@/Components/ReturnProofDialog.vue';

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
  if (props.rental.is_overdue) {
    if (!hasVerifiedOverduePayment.value) return false;
  }
  
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

// Add computed properties for payment states
const paymentRequest = computed(() => 
  props.rental.payment_request?.type === 'overdue' ? props.rental.payment_request : null
);

const paymentStatus = computed(() => paymentRequest.value?.status || null);

const hasPendingOverduePayment = computed(() => paymentStatus.value === 'pending');
const hasVerifiedOverduePayment = computed(() => paymentStatus.value === 'verified');
const hasRejectedOverduePayment = computed(() => paymentStatus.value === 'rejected');

// Add computed for return states
const canInitiateReturn = computed(() => 
  props.rental.status === 'active' && 
  props.userRole === 'renter' && 
  (!props.rental.is_overdue || hasVerifiedOverduePayment.value)
);

const showWaitingMessage = computed(() => 
  props.rental.status === 'pending_return' && 
  props.userRole === 'lender' && 
  !props.rental.return_schedules?.some(s => s.is_selected)
);

// Add ref for dialogs
const showOverduePayment = ref(false);
const showReturnProofDialog = ref(false);
const returnProofType = ref('submit');

// Add computed for selected schedule details
const selectedScheduleDetails = computed(() => {
  if (!selectedSchedule.value) return null;

  const date = new Date(selectedSchedule.value.return_datetime);
  return {
    dayOfWeek: format(date, 'EEEE'),
    date: format(date, 'MMMM d, yyyy'),
    timeFrame: `${formatTimeString(selectedSchedule.value.start_time)} to ${formatTimeString(selectedSchedule.value.end_time)}`
  };
});

// Add helper function for time formatting
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

// Add handlers for return proof actions
const handleSubmitReturn = () => {
  returnProofType.value = 'submit';
  showReturnProofDialog.value = true;
};

const handleConfirmReturn = () => {
  returnProofType.value = 'confirm';
  showReturnProofDialog.value = true;
};
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
          <!-- Initial unpaid state -->
          <template v-if="!paymentRequest">
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
                <p>Your overdue payment has been submitted and is pending verification.</p>
                <p class="font-medium mt-2">
                  Reference Number: {{ paymentRequest.reference_number }}
                </p>
                <p class="font-medium">Amount Paid: {{ formatNumber(rental.overdue_fee) }}</p>
              </AlertDescription>
            </Alert>
            <div class="mt-4 p-4 bg-muted rounded-lg">
              <p class="text-sm text-muted-foreground text-center">
                Please wait while we verify your payment. You will be notified once verified.
              </p>
            </div>
          </template>

          <!-- Rejected payment state -->
          <template v-else-if="hasRejectedOverduePayment">
            <Alert variant="destructive">
              <AlertDescription class="space-y-2">
                <p>Your overdue payment was rejected for the following reason:</p>
                <p class="font-medium mt-2">{{ paymentRequest.admin_feedback }}</p>
                <p class="mt-2">Please submit a new payment with the correct details.</p>
              </AlertDescription>
            </Alert>
            
            <div class="flex gap-2 mt-4">
              <Button 
                variant="default" 
                @click="showOverduePayment = true"
              >
                Submit New Payment
              </Button>
              <Button 
                variant="outline" 
                disabled
              >
                Initiate Return
              </Button>
            </div>
          </template>

          <!-- Verified payment state -->
          <template v-else-if="hasVerifiedOverduePayment">
            <Alert variant="success">
              <AlertDescription class="space-y-2">
                <p>Your overdue payment has been verified successfully!</p>
                <p class="font-medium">Payment Details:</p>
                <ul class="space-y-1 mt-2">
                  <li>Reference: {{ paymentRequest.reference_number }}</li>
                  <li>Amount: {{ formatNumber(rental.overdue_fee) }}</li>
                  <li>Verified: {{ formatDateTime(paymentRequest.verified_at) }}</li>
                </ul>
              </AlertDescription>
            </Alert>
            
            <div class="mt-4">
              <Button 
                class="w-full" 
                @click="handleInitiateReturn"
                :disabled="initiateForm.processing"
              >
                Proceed with Return
              </Button>
            </div>
          </template>
        </template>

        <!-- Lender view for overdue states -->
        <template v-else>
          <template v-if="!paymentRequest">
            <Alert variant="warning">
              <AlertDescription class="space-y-2">
                <p>This rental is overdue. Waiting for the renter to submit the overdue payment.</p>
                <p class="font-medium">Outstanding Fee: {{ formatNumber(rental.overdue_fee) }}</p>
              </AlertDescription>
            </Alert>
          </template>

          <template v-else-if="hasPendingOverduePayment">
            <Alert variant="warning">
              <AlertDescription class="space-y-2">
                <p>The renter has submitted an overdue payment.</p>
                <p class="font-medium mt-2">
                  Reference Number: {{ paymentRequest.reference_number }}
                </p>
                <p class="font-medium">Amount: {{ formatNumber(rental.overdue_fee) }}</p>
              </AlertDescription>
            </Alert>
            <div class="mt-4 p-4 bg-muted rounded-lg">
              <p class="text-sm text-muted-foreground text-center">
                Please wait for admin verification before proceeding with the return process.
              </p>
            </div>
          </template>

          <!-- Add rejected payment state for lender -->
          <template v-else-if="hasRejectedOverduePayment">
            <Alert variant="destructive">
              <AlertDescription class="space-y-2">
                <p>The renter's overdue payment was rejected by admin:</p>
                <p class="font-medium mt-2">{{ paymentRequest.admin_feedback }}</p>
                <p class="mt-2">The renter will need to submit a new payment.</p>
                <p class="font-medium mt-2">Outstanding Fee: {{ formatNumber(rental.overdue_fee) }}</p>
              </AlertDescription>
            </Alert>
            <div class="mt-4 p-4 bg-muted rounded-lg">
              <p class="text-sm text-muted-foreground text-center">
                Waiting for the renter to submit a new payment...
              </p>
            </div>
          </template>

          <template v-else-if="hasVerifiedOverduePayment">
            <Alert variant="success">
              <AlertDescription class="space-y-2">
                <p>The renter's overdue payment has been verified.</p>
                <p>The return process can begin once the renter initiates it.</p>
                <p class="font-medium mt-2">
                  Verified Payment Details:
                </p>
                <ul class="space-y-1 mt-1">
                  <li>Reference: {{ paymentRequest.reference_number }}</li>
                  <li>Amount: {{ formatNumber(rental.overdue_fee) }}</li>
                </ul>
              </AlertDescription>
            </Alert>
          </template>
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

  <!-- Add Early Return Confirmation Dialog -->
  <ConfirmDialog
    v-model:show="showEarlyReturnDialog"
    title="Early Return Notice"
    description="We noticed you're returning this item before the rental period ends. Here's what you need to know:"
    confirmLabel="Yes, Initiate Return"
    cancelLabel="No, Keep Renting"
    :processing="initiateForm.processing"
    @confirm="proceedWithReturn"
    @cancel="showEarlyReturnDialog = false"
  >
    <ul class="space-y-2 mt-4 text-sm text-muted-foreground">
      <li class="flex items-start gap-2">
        <span class="text-primary">•</span>
        <span>Your rental payment for the remaining days cannot be refunded</span>
      </li>
      <li class="flex items-start gap-2">
        <span class="text-primary">•</span>
        <span>Your security deposit will be returned after we verify the item's condition</span>
      </li>
      <li class="flex items-start gap-2">
        <span class="text-primary">•</span>
        <span>The lender will need to confirm the return schedule</span>
      </li>
    </ul>
  </ConfirmDialog>

</template>