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

// Handle schedule confirmation by lender
const handleConfirmSchedule = () => {
  if (!selectedSchedule.value) return;
  
  confirmForm.patch(route('return-schedules.confirm', {
    rental: props.rental.id
  }), {
    preserveScroll: true,
    onSuccess: () => {
      // Show success state is handled by template through confirmedSchedule computed
      console.log('Schedule confirmed successfully');
    }
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

  const scheduleDate = new Date(selectedSchedule.value.return_datetime);
  return {
    dayOfWeek: format(scheduleDate, 'EEEE'),
    date: format(scheduleDate, 'MMMM d, yyyy'),
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

// Add new computed for schedule confirmation message
const scheduleConfirmationMessage = computed(() => {
  if (!confirmedSchedule.value) return null;

  const date = format(new Date(confirmedSchedule.value.return_datetime), 'MMMM d, yyyy');
  const time = `${formatTimeString(confirmedSchedule.value.start_time)} to ${formatTimeString(confirmedSchedule.value.end_time)}`;
  
  if (props.userRole === 'lender') {
    return {
      title: 'Return Schedule Confirmed',
      message: `You have confirmed the return schedule. Please wait for the renter to return the item on ${date} between ${time}.`
    };
  }
  return {
    title: 'Return Schedule Confirmed',
    message: `The lender has confirmed your return schedule. Please return the item on ${date} between ${time}.`
  };
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
                Submit Overdue Payment
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

          <!-- Add Return Schedule Section similar to Pickup Schedule -->
          <Card v-if="selectedSchedule" class="shadow-sm">
            <CardHeader class="bg-card border-b">
              <CardTitle class="text-lg">Return Details</CardTitle>
            </CardHeader>
            <CardContent class="p-6">
              <div class="space-y-6">
                <!-- Meetup Location -->
                <div class="space-y-2">
                  <h4 class="font-medium">Meetup Location</h4>
                  <div class="p-4 border rounded-lg bg-muted/30">
                    <div class="space-y-2">
                      <p class="font-medium">{{ rental.listing.location.address }}</p>
                      <p class="text-muted-foreground text-sm">
                        {{ rental.listing.location.city }},
                        {{ rental.listing.location.province }}
                        {{ rental.listing.location.postal_code }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Schedule Information -->
                <div class="space-y-2">
                  <h4 class="font-medium">Scheduled Time</h4>
                  <div class="p-4 border rounded-lg bg-muted/30">
                    <div class="space-y-3">
                      <div class="flex items-baseline justify-between">
                        <span class="font-medium">{{ format(new Date(selectedSchedule.return_datetime), 'EEEE') }}</span>
                        <span class="text-sm text-muted-foreground">
                          {{ format(new Date(selectedSchedule.return_datetime), 'MMMM d, yyyy') }}
                        </span>
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">Time Frame</span>
                        <span class="font-medium">
                          {{ formatTimeString(selectedSchedule.start_time) }} to {{ formatTimeString(selectedSchedule.end_time) }}
                        </span>
                      </div>
                      <!-- Add Status Message -->
                      <div class="mt-2 pt-2 border-t">
                        <p class="text-sm" :class="selectedSchedule.is_confirmed ? 'text-primary' : 'text-muted-foreground'">
                          <template v-if="selectedSchedule.is_confirmed">
                            {{ userRole === 'renter' ? 'Schedule confirmed by lender' : 'You have confirmed this schedule' }}
                          </template>
                          <template v-else>
                            {{ userRole === 'renter' ? 'Awaiting lender confirmation' : 'Schedule needs your confirmation' }}
                          </template>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Important Notes -->
                <div class="space-y-2">
                  <h4 class="font-medium">Important Notes</h4>
                  <ul class="space-y-2 text-sm text-muted-foreground">
                    <li class="flex items-center gap-2">
                      <span class="text-primary">•</span>
                      <span>Please arrive at the meetup location on time</span>
                    </li>
                    <li class="flex items-center gap-2">
                      <span class="text-primary">•</span>
                      <span>Take photos of the item during handover for proof</span>
                    </li>
                  </ul>
                </div>

                <!-- Lender Confirmation Button -->
                <div v-if="userRole === 'lender' && !selectedSchedule.is_confirmed">
                  <Button 
                    class="w-full" 
                    @click="handleConfirmSchedule"
                    :disabled="confirmForm.processing"
                  >
                    Confirm Return Schedule
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Rest of the existing template code -->
          <!-- Waiting message - Only visible to lender -->
          <div 
            v-if="showWaitingMessage" 
            class="p-4 text-center text-muted-foreground bg-muted/30 rounded-lg"
          >
            Waiting for renter to select a return schedule...
          </div>

          <!-- Confirmed Schedule Display -->
          <div v-if="confirmedSchedule" class="space-y-4">
            <Alert variant="success">
              <AlertDescription class="space-y-2">
                <h4 class="font-medium">{{ scheduleConfirmationMessage.title }}</h4>
                <p>{{ scheduleConfirmationMessage.message }}</p>
              </AlertDescription>
            </Alert>

            <div class="p-4 border rounded-lg bg-muted/50">
              <div class="space-y-2">
                <h3 class="font-medium">Scheduled Return Details</h3>
                <div class="space-y-1 text-sm">
                  <p>
                    <span class="text-muted-foreground">Date:</span> 
                    {{ format(new Date(confirmedSchedule.return_datetime), 'MMMM d, yyyy') }}
                  </p>
                  <p>
                    <span class="text-muted-foreground">Time:</span>
                    {{ formatTimeString(confirmedSchedule.start_time) }} to {{ formatTimeString(confirmedSchedule.end_time) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Role-specific instructions -->
            <div class="p-4 bg-muted/30 rounded-lg">
              <p class="text-sm text-muted-foreground text-center">
                <template v-if="userRole === 'lender'">
                  Once the renter returns the item, they will submit a return proof for your confirmation.
                </template>
                <template v-else>
                  Please make sure to return the item during the scheduled time. You'll need to submit a return proof after.
                </template>
              </p>
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
    description="We noticed you're returning this item before the rental period ends. Here's what you need to know: 
    
    Your rental payment for the remaining days cannot be refunded.

    Your security deposit will be returned after we verify the item's condition.
    
    The lender will need to confirm the return schedule."
    
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