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
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

const props = defineProps({
  show: Boolean,
  rental: Object,
  disputeReasons: {
    type: Array,
    default: () => [
      { id: 1, label: 'Physical Damage', value: 'physical_damage' },
      { id: 2, label: 'Missing Parts or Accessories', value: 'missing_parts' },
      { id: 3, label: 'Unauthorized Modifications', value: 'unauthorized_mods' },
      { id: 4, label: 'Improper Use/Abuse', value: 'improper_use' },
      { id: 5, label: 'Functionality Issues', value: 'functionality' },
      { id: 6, label: 'Signs of Tampering', value: 'tampering' },
      { id: 7, label: 'Water/Liquid Damage', value: 'water_damage' },
      { id: 8, label: 'Electrical/Technical Issues', value: 'electrical_issues' },
      { id: 9, label: 'Cosmetic Changes', value: 'cosmetic_changes' },
      { id: 10, label: 'Hygiene/Cleanliness Issues', value: 'hygiene_issues' },
      { id: 11, label: 'Software/Data Tampering', value: 'software_tampering' },
      { id: 12, label: 'Excessive Wear and Tear', value: 'excessive_wear' },
      { id: 13, label: 'Other', value: 'other' }
    ]
  }
});

const emit = defineEmits(['update:show']);

const disputeForm = useForm({
  reason: '',
  issue_description: '',
  proof_image: null
});

const selectedDisputeImage = ref([]);

const isOtherReason = computed(() => disputeForm.reason === 'other');

const handleDisputeSubmit = () => {
  disputeForm.proof_image = selectedDisputeImage.value[0];
  
  disputeForm.post(route('rentals.raise-dispute', props.rental.id), {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:show', false);
      disputeForm.reset();
      selectedDisputeImage.value = [];
    }
  });
};
</script>

<template>
  <Dialog :open="show" @update:open="emit('update:show', $event)">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>Raise Return Dispute</DialogTitle>
        <DialogDescription>
          Please provide details about any issues with the returned item
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleDisputeSubmit" class="space-y-4">
        <!-- Add Reason Selector -->
        <div class="space-y-2">
          <label class="text-sm font-medium">Select Dispute Reason</label>
          <Select v-model="disputeForm.reason" :error="disputeForm.errors.reason">
            <SelectTrigger>
              <SelectValue placeholder="Select a reason for the dispute" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem 
                v-for="reason in disputeReasons" 
                :key="reason.id"
                :value="reason.value"
              >
                {{ reason.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <!-- Photo Evidence Upload -->
        <div class="space-y-2">
          <label class="text-sm font-medium">Upload Photo Evidence</label>
          <ImageUpload
            :maxFiles="1"
            @images="selectedDisputeImage = $event"
            :error="disputeForm.errors.proof_image"
            class="w-full aspect-video"
          />
          <p class="text-xs text-muted-foreground">
            Please provide clear photos of the issues or damages
          </p>
        </div>

        <!-- Issue Description -->
        <div class="space-y-2">
          <label class="text-sm font-medium">
            {{ isOtherReason ? 'Describe Your Issue' : 'Additional Details' }}
          </label>
          <Textarea
            v-model="disputeForm.issue_description"
            :placeholder="isOtherReason ? 
              'Please provide specific details about your dispute...' : 
              'Add any additional information about the selected issue...'
            "
            :error="disputeForm.errors.issue_description"
            rows="4"
          />
        </div>

        <!-- Action Buttons -->
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
            variant="destructive"
            :disabled="!disputeForm.reason || 
                      !disputeForm.issue_description || 
                      selectedDisputeImage.length === 0 || 
                      disputeForm.processing"
          >
            Submit Dispute
          </Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
