<script setup>
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { formatDateTime } from "@/lib/formatters";
import { Button } from "@/components/ui/button";
import { 
  Card, 
  CardContent, 
  CardHeader, 
  CardTitle 
} from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

const props = defineProps({
  disputes: Object,
});

const showResolveDialog = ref(false);
const selectedDispute = ref(null);
const resolutionType = ref("");
const resolutionNotes = ref("");
const isProcessing = ref(false);

const openResolveDialog = (dispute) => {
  selectedDispute.value = dispute;
  showResolveDialog.value = true;
};

const handleResolve = () => {
  if (!selectedDispute.value || !resolutionType.value || !resolutionNotes.value) {
    return;
  }

  isProcessing.value = true;
  router.post(
    route("admin.handover-disputes.resolve", selectedDispute.value.id),
    {
      resolution_type: resolutionType.value,
      resolution_notes: resolutionNotes.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        showResolveDialog.value = false;
        selectedDispute.value = null;
        resolutionType.value = "";
        resolutionNotes.value = "";
      },
      onFinish: () => {
        isProcessing.value = false;
      },
    }
  );
};

const resolutionOptions = [
  { value: "cancel_with_refund", label: "Cancel with Full Refund" },
  { value: "cancel_with_penalty", label: "Cancel with Penalty" },
  { value: "reschedule", label: "Reschedule Handover" },
  { value: "rejected", label: "Reject Dispute" },
];
</script>

<template>
  <Head title="| Handover Disputes" />

  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-semibold tracking-tight">Handover Disputes</h2>
    </div>

    <div class="grid gap-4">
      <Card v-for="dispute in disputes.data" :key="dispute.id" class="shadow-sm">
        <CardHeader class="bg-card border-b">
          <CardTitle class="flex items-center justify-between">
            <div>
              Dispute #{{ dispute.id }}
              <span 
                :class="[
                  'ml-2 text-sm px-2 py-1 rounded-full',
                  {
                    'bg-amber-100 text-amber-700': dispute.status === 'pending',
                    'bg-green-100 text-green-700': dispute.status === 'resolved',
                  }
                ]"
              >
                {{ dispute.status }}
              </span>
            </div>
            <span class="text-sm text-muted-foreground">
              Reported {{ formatDateTime(dispute.created_at) }}
            </span>
          </CardTitle>
        </CardHeader>

        <CardContent class="p-6">
          <div class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
              <div>
                <h4 class="font-medium mb-2">Type</h4>
                <p class="text-sm">
                  {{ dispute.type === 'lender_no_show' ? 'Lender No-Show' : 'Renter No-Show' }}
                </p>
              </div>

              <div>
                <h4 class="font-medium mb-2">Reported By</h4>
                <p class="text-sm">{{ dispute.raised_by?.name }}</p>
              </div>
            </div>

            <div>
              <h4 class="font-medium mb-2">Description</h4>
              <p class="text-sm text-muted-foreground">{{ dispute.description }}</p>
            </div>

            <div>
              <h4 class="font-medium mb-2">Location Proof</h4>
              <img 
                :src="`/storage/${dispute.proof_path}`"
                class="w-full max-w-md rounded-lg border"
                alt="Location proof"
              />
            </div>

            <div class="flex justify-end">
              <Button 
                v-if="dispute.status === 'pending'"
                @click="openResolveDialog(dispute)"
              >
                Review & Resolve
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>

  <!-- Resolve Dialog -->
  <Dialog :open="showResolveDialog" @update:open="showResolveDialog = $event">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>Resolve Dispute</DialogTitle>
        <DialogDescription>
          Please review the dispute and select an appropriate resolution.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <div class="space-y-2">
          <label class="text-sm font-medium">Resolution Type</label>
          <Select v-model="resolutionType">
            <SelectTrigger>
              <SelectValue placeholder="Select resolution type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem 
                v-for="option in resolutionOptions" 
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">Resolution Notes</label>
          <Textarea
            v-model="resolutionNotes"
            placeholder="Provide details about your decision..."
            rows="4"
          />
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="showResolveDialog = false">
          Cancel
        </Button>
        <Button
          variant="default"
          :disabled="!resolutionType || !resolutionNotes || isProcessing"
          @click="handleResolve"
        >
          {{ isProcessing ? 'Processing...' : 'Submit Resolution' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
