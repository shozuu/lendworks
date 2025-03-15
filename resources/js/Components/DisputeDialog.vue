<script setup>
import { ref } from 'vue';
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

const props = defineProps({
  show: Boolean,
  rental: Object,
});

const emit = defineEmits(['update:show']);

const disputeForm = useForm({
  issue_description: '',
  proof_image: null
});

const selectedDisputeImage = ref([]);

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

        <div class="space-y-2">
          <label class="text-sm font-medium">Describe the Issue</label>
          <Textarea
            v-model="disputeForm.issue_description"
            placeholder="Please provide detailed information about the issues encountered..."
            :error="disputeForm.errors.issue_description"
            rows="4"
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
            variant="destructive"
            :disabled="!disputeForm.issue_description || 
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
