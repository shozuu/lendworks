<script setup>
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { formatDateTime, formatNumber } from "@/lib/formatters";

const props = defineProps({
  show: Boolean,
  payment: Object,
});

const emit = defineEmits(['update:show']);
</script>

<template>
  <Dialog :open="show" @update:open="$emit('update:show', $event)">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>Payment Proof</DialogTitle>
      </DialogHeader>

      <div class="space-y-4">
        <img 
          :src="`/storage/${payment.proof_path}`" 
          :alt="'Payment proof'" 
          class="w-full rounded-lg"
        />

        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-muted-foreground">Reference Number:</span>
            <span class="font-medium">{{ payment.reference_number }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Amount:</span>
            <span class="font-medium">{{ formatNumber(payment.amount) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-muted-foreground">Processed on:</span>
            <span class="font-medium">{{ formatDateTime(payment.processed_at) }}</span>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
