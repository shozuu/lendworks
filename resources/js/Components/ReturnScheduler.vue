<script setup>
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import { formatDateTime } from "@/lib/formatters";
import { addDays, format } from "date-fns";
import ReturnProofDialog from '@/Components/ReturnProofDialog.vue';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const props = defineProps({
  rental: Object,
  userRole: String,
});

const initiateForm = useForm({});
const scheduleForm = useForm({
  return_datetime: '',
});
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

// Generate available return dates (starting from rental end date)
const availableDates = computed(() => {
  if (!props.rental.end_date) return [];
  
  const endDate = new Date(props.rental.end_date);
  const dates = [];
  
  // Generate next 7 days from end date
  for (let i = 0; i <= 7; i++) {
    const date = addDays(endDate, i);
    dates.push({
      value: format(date, 'yyyy-MM-dd'),
      label: format(date, 'EEEE, MMMM d, yyyy')
    });
  }
  
  return dates;
});

// Available time slots
const timeSlots = [
  { value: '09:00', label: '9:00 AM' },
  { value: '10:00', label: '10:00 AM' },
  { value: '11:00', label: '11:00 AM' },
  { value: '13:00', label: '1:00 PM' },
  { value: '14:00', label: '2:00 PM' },
  { value: '15:00', label: '3:00 PM' },
  { value: '16:00', label: '4:00 PM' },
];

const handleScheduleSubmit = () => {
  if (!selectedDate.value || !selectedTime.value) return;
  
  const datetime = `${selectedDate.value} ${selectedTime.value}:00`;
  
  scheduleForm.post(route('return-schedules.store', props.rental.id), {
    data: { return_datetime: datetime },
    preserveScroll: true,
    onSuccess: () => {
      selectedDate.value = '';
      selectedTime.value = '';
    }
  });
};
</script>

<template>
  <Card class="shadow-sm">
    <CardHeader class="bg-card border-b">
      <CardTitle>Return Process</CardTitle>
    </CardHeader>
    <CardContent class="p-6">
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

        <!-- Return Schedule Selection -->
        <div v-if="rental.status === 'pending_return'" class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Select Return Date</label>
            <Select v-model="selectedDate">
              <SelectTrigger>
                <SelectValue placeholder="Choose a date" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="date in availableDates" 
                  :key="date.value" 
                  :value="date.value"
                >
                  {{ date.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Select Time</label>
            <Select v-model="selectedTime">
              <SelectTrigger>
                <SelectValue placeholder="Choose a time" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="slot in timeSlots" 
                  :key="slot.value" 
                  :value="slot.value"
                >
                  {{ slot.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <Button 
            class="w-full" 
            :disabled="!selectedDate || !selectedTime || scheduleForm.processing"
            @click="handleScheduleSubmit"
          >
            Propose Return Schedule
          </Button>
        </div>

        <!-- Selected Schedule Display -->
        <div v-if="selectedSchedule" class="p-4 border rounded-lg">
          <div class="space-y-2">
            <h3 class="font-medium">Selected Return Schedule</h3>
            <p class="text-sm">{{ formatDateTime(selectedSchedule.return_datetime) }}</p>
            
            <Button 
              v-if="userRole === 'lender' && !selectedSchedule.is_confirmed"
              class="mt-2"
              @click="handleConfirmSchedule(selectedSchedule)"
              :disabled="confirmForm.processing"
            >
              Confirm Schedule
            </Button>
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
    </CardContent>
  </Card>
</template>
