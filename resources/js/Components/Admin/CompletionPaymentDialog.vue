<script setup>
import { ref, computed } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import ImageUpload from "@/Components/ImageUpload.vue";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { formatNumber } from "@/lib/formatters";

const props = defineProps({
  show: Boolean,
  rental: Object,
  type: {
    type: String,
    validator: (value) => ['lender_payment', 'deposit_refund'].includes(value)
  }
});

const emit = defineEmits(['update:show']);

// Update the earnings computed property with debugging
const earnings = computed(() => {
  if (!props.rental) {
    console.log('No rental data available');
    return { base: 0, overdue: 0, total: 0, hasOverdue: false };
  }
  
  // Debug log the entire rental object
  console.log('Full rental data:', {
    id: props.rental.id,
    base_price: props.rental.base_price,
    discount: props.rental.discount,
    service_fee: props.rental.service_fee,
    earnings: props.rental.lender_earnings
  });
  
  if (!props.rental.lender_earnings) {
    console.log('No lender_earnings data available');
    return { base: 0, overdue: 0, total: 0, hasOverdue: false };
  }
  
  return props.rental.lender_earnings;
});

const form = useForm({
  proof_image: null,
  reference_number: '',
  notes: '',
  amount: computed(() => {
    if (props.type === 'lender_payment') {
      // Debug log
      console.log('Form amount calculation:', earnings.value);
      return earnings.value.total;
    }
    return props.rental?.deposit_fee || 0;
  })
});

const selectedImage = ref([]);

const handleSubmit = () => {
  const endpoint = props.type === 'lender_payment' 
    ? route('admin.completion-payments.store-lender-payment', props.rental.id)
    : route('admin.completion-payments.store-deposit-refund', props.rental.id);

  form.proof_image = selectedImage.value[0];
  
  form.post(endpoint, {
    preserveScroll: true,
    onSuccess: (response) => {
      // Add debug logging
      console.log('Payment Response:', response);
      
      emit('update:show', false);
      form.reset();
      selectedImage.value = [];
    },
    onError: (errors) => {
      console.error('Payment Error:', errors);
    }
  });
};

const title = computed(() => 
  props.type === 'lender_payment' ? 'Process Lender Payment' : 'Process Deposit Refund'
);

const description = computed(() => 
  props.type === 'lender_payment' 
    ? 'Submit proof of payment sent to the lender'
    : 'Submit proof of deposit refund sent to the renter'
);
</script>

<template>
  <Dialog :open="show" @update:open="emit('update:show', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>{{ description }}</DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="space-y-2">
          <label class="text-sm font-medium">Amount</label>
          <!-- Lender Payment Amount Display -->
          <div v-if="type === 'lender_payment'" class="space-y-2 p-4 bg-muted rounded-lg">
            <div class="space-y-1 text-sm">
              <!-- Debug info -->
              <div class="text-xs mb-2 p-2 bg-secondary">
                <div>Base Price: {{ rental.base_price }}</div>
                <div>Discount: {{ rental.discount }}</div>
                <div>Service Fee: {{ rental.service_fee }}</div>
                <div>Raw Earnings: {{ JSON.stringify(rental.lender_earnings) }}</div>
              </div>
              
              <!-- Debug display -->
              <pre class="text-xs mb-2">{{ JSON.stringify(earnings, null, 2) }}</pre>
              
              <div class="flex justify-between">
                <span class="text-muted-foreground">Base Earnings:</span>
                <span>{{ formatNumber(earnings.base) }}</span>
              </div>
              <div v-if="earnings.hasOverdue" class="flex justify-between">
                <span class="text-muted-foreground">Overdue Fee:</span>
                <span class="text-emerald-500">+ {{ formatNumber(earnings.overdue) }}</span>
              </div>
              <div class="flex justify-between font-medium pt-2 border-t mt-2">
                <span>Total Payment:</span>
                <span>{{ formatNumber(earnings.total) }}</span>
              </div>
              <p class="text-xs text-muted-foreground mt-2">
                This is the total earnings amount that will be sent to the lender.
              </p>
            </div>
          </div>

          <!-- Deposit Refund Breakdown -->
          <div v-else class="space-y-2 p-4 bg-muted rounded-lg">
            <div class="space-y-1 text-sm">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Security Deposit Amount:</span>
                <span>{{ formatNumber(rental.deposit_fee) }}</span>
              </div>
              <div class="flex justify-between font-medium pt-2 border-t mt-2">
                <span>Total Refund:</span>
                <span>{{ formatNumber(form.amount) }}</span>
              </div>
              <p class="text-xs text-muted-foreground mt-2">
                This is the full deposit amount that will be refunded to the renter.
              </p>
            </div>
          </div>

          <Input
            v-model="form.amount"
            type="number"
            :error="form.errors.amount"
            :readonly="true"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">Reference Number</label>
          <Input
            v-model="form.reference_number"
            :error="form.errors.reference_number"
            placeholder="Enter payment reference number"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">Upload Payment Proof</label>
          <ImageUpload
            :maxFiles="1"
            @images="selectedImage = $event"
            :error="form.errors.proof_image"
            class="w-full aspect-video"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">Notes (Optional)</label>
          <Textarea
            v-model="form.notes"
            :error="form.errors.notes"
            placeholder="Add any additional notes..."
            rows="3"
          />
        </div>

        <div class="flex justify-end gap-2">
          <Button
            type="button"
            variant="outline"
            @click="emit('update:show', false)"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            :disabled="!form.reference_number || selectedImage.length === 0 || form.processing"
          >
            Submit
          </Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
