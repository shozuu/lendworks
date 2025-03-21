<script setup>
import { ref, computed, watch } from 'vue';
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

// Update the earnings computed property to include dispute deductions
const earnings = computed(() => {
  if (!props.rental) return { base: 0, overdue: 0, total: 0 };
  
  const baseEarnings = props.rental.base_price - props.rental.discount - props.rental.service_fee;
  const overdueFee = props.rental.overdue_fee || 0;
  const disputeDeduction = props.rental.dispute?.resolution_type === 'deposit_deducted' 
    ? (props.rental.dispute?.deposit_deduction || 0) 
    : 0;
  
  return {
    base: baseEarnings,
    overdue: overdueFee,
    dispute: disputeDeduction,
    total: baseEarnings + overdueFee + disputeDeduction
  };
});

// Update the form initialization
const form = useForm({
  proof_image: null,
  reference_number: '',
  notes: '',
  amount: props.type === 'lender_payment' 
    ? earnings.value.total
    : (props.rental?.deposit_fee - (props.rental?.dispute?.resolution_type === 'deposit_deducted' ? props.rental?.dispute?.deposit_deduction : 0)) || 0
});

// Add watch to update amount when dispute status changes
watch(
  [() => props.rental?.dispute],
  ([newDispute]) => {
    if (props.type === 'lender_payment') {
      form.amount = earnings.value.total;
    } else {
      const depositDeduction = newDispute?.resolution_type === 'deposit_deducted' 
        ? (newDispute?.deposit_deduction || 0) 
        : 0;
      form.amount = props.rental?.deposit_fee - depositDeduction;
    }
  },
  { immediate: true }
);

const selectedImage = ref([]);

const handleSubmit = () => {
  if (!selectedImage.value[0]) {
    console.error('No image selected');
    return;
  }

  form.proof_image = selectedImage.value[0];
  
  const endpoint = props.type === 'lender_payment' 
    ? route('admin.completion-payments.store-lender-payment', props.rental.id)
    : route('admin.completion-payments.store-deposit-refund', props.rental.id);

  form.post(endpoint, {
    forceFormData: true,
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      emit('update:show', false);
      form.reference_number = '';
      form.notes = '';
      form.proof_image = null;
      selectedImage.value = [];
      window.location.reload();
    },
    onError: (errors) => {
      console.error('Submission errors:', errors);
    }
  });
};

// Add a new watch for selectedImage
watch(selectedImage, (newImages) => {
  if (newImages.length > 0) {
    form.proof_image = newImages[0];
  } else {
    form.proof_image = null;
  }
});

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

      <form @submit.prevent="handleSubmit" class="space-y-4" enctype="multipart/form-data">
        <div class="space-y-2">
          <label class="text-sm font-medium">Amount</label>
          <!-- Lender Payment Amount Display -->
          <div v-if="type === 'lender_payment'" class="space-y-2 p-4 bg-muted rounded-lg">
            <div class="space-y-1 text-sm">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Base Price:</span>
                <span>{{ formatNumber(rental.base_price) }}</span>
              </div>
              <div v-if="rental.discount > 0" class="flex justify-between">
                <span class="text-muted-foreground">Discount:</span>
                <span class="text-destructive">- {{ formatNumber(rental.discount) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-muted-foreground">Service Fee:</span>
                <span class="text-destructive">- {{ formatNumber(rental.service_fee) }}</span>
              </div>
              <div v-if="rental.overdue_fee > 0" class="flex justify-between">
                <span class="text-muted-foreground">Overdue Fee:</span>
                <span class="text-emerald-500">+ {{ formatNumber(rental.overdue_fee) }}</span>
              </div>
              <!-- Add dispute deduction if exists -->
              <div v-if="rental.dispute?.deposit_deduction > 0" class="flex justify-between">
                <span class="text-muted-foreground">Dispute Deduction:</span>
                <span class="text-emerald-500">+ {{ formatNumber(rental.dispute.deposit_deduction) }}</span>
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
              <!-- Add dispute deduction if exists -->
              <div v-if="rental.dispute?.deposit_deduction > 0" class="flex justify-between">
                <span class="text-muted-foreground">Dispute Deduction:</span>
                <span class="text-destructive">- {{ formatNumber(rental.dispute.deposit_deduction) }}</span>
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
            accept="image/*"
          />
          <p v-if="form.errors.proof_image" class="text-sm text-destructive">
            {{ form.errors.proof_image }}
          </p>
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
