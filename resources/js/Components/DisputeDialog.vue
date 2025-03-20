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

const MAX_ADDITIONAL_IMAGES = 4;
const showAdditionalUpload = ref(false);
const mainProofImage = ref([]);
const additionalImages = ref([]);

const selectedDisputeImage = computed(() => {
  return [...mainProofImage.value, ...additionalImages.value];
});

const disputeForm = useForm({
  reason: '',
  issue_description: '',
  proof_image: null,
  additional_images: []
});

const isOtherReason = computed(() => disputeForm.reason === 'other');

const handleDisputeSubmit = () => {
  console.log('Starting dispute submission...', {
    reason: disputeForm.reason,
    description: disputeForm.issue_description,
    mainImage: mainProofImage.value[0],
    additionalImages: additionalImages.value
  });

  // Reset previous errors
  disputeForm.clearErrors();

  // Create form data
  let formData = new FormData();
  formData.append('reason', disputeForm.reason);
  formData.append('issue_description', disputeForm.issue_description);

  // Explicitly set proof_image and additional_images
  disputeForm.proof_image = mainProofImage.value[0];
  disputeForm.additional_images = additionalImages.value;

  // Append main proof image
  if (mainProofImage.value[0]) {
    formData.append('proof_image', mainProofImage.value[0]);
  }

  // Append additional images if any
  if (additionalImages.value.length > 0) {
    additionalImages.value.forEach((image, index) => {
      formData.append(`additional_images[${index}]`, image);
    });
  }

  console.log('Form data prepared:', {
    hasReason: !!disputeForm.reason,
    hasDescription: !!disputeForm.issue_description,
    totalImages: selectedDisputeImage.value.length
  });

  disputeForm.post(route('rentals.raise-dispute', props.rental.id), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      console.log('Dispute submitted successfully with', selectedDisputeImage.value.length, 'images');
      emit('update:show', false);
      disputeForm.reset();
      mainProofImage.value = [];
      additionalImages.value = [];
      showAdditionalUpload.value = false;
    },
    onError: (errors) => {
      console.error('Dispute submission failed:', errors);
    },
    onStart: () => {
      console.log('Starting form submission...');
    },
    onFinish: () => {
      console.log('Form submission completed');
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

        <!-- Main Photo Evidence Upload -->
        <div class="space-y-2">
          <label class="text-sm font-medium">Upload Main Proof Photo</label>
          <ImageUpload
            :maxFiles="1"
            @images="mainProofImage = $event"
            :error="disputeForm.errors.proof_image"
            class="w-full aspect-video"
          />
          <p class="text-xs text-muted-foreground">
            Upload a clear photo showing the main issue
          </p>
        </div>

        <!-- Option to add more photos -->
        <div v-if="mainProofImage.length > 0" class="space-y-4">
          <div class="flex items-center justify-between">
            <Button 
              type="button" 
              variant="outline" 
              size="sm"
              @click="showAdditionalUpload = !showAdditionalUpload"
            >
              {{ showAdditionalUpload ? 'Hide Additional Upload' : 'Add More Photos' }}
            </Button>
            <span v-if="additionalImages.length > 0" class="text-xs text-muted-foreground">
              {{ additionalImages.length }} additional photo(s)
            </span>
          </div>

          <!-- Additional Photos Upload -->
          <div v-if="showAdditionalUpload" class="space-y-2">
            <ImageUpload
              :maxFiles="MAX_ADDITIONAL_IMAGES"
              @images="additionalImages = $event"
              class="w-full aspect-video"
              multiple
            />
            <p class="text-xs text-muted-foreground">
              Upload up to {{ MAX_ADDITIONAL_IMAGES }} additional photos as evidence
            </p>
          </div>
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
                      mainProofImage.length === 0 || 
                      disputeForm.processing"
          >
            {{ disputeForm.processing ? "Submitting..." : "Submit Dispute" }}
          </Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
