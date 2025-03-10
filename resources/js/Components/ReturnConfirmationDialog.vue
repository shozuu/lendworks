<script setup>
import { ref, computed, watchEffect, watch } from 'vue';
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
  type: {
    type: String,
    validator: (value) => ['submit', 'confirm', 'finalize'].includes(value)
  }
});

const emit = defineEmits(['update:show']);

const form = useForm({
  proof_image: null
});

const selectedImage = ref([]);

// Add new refs for dispute functionality
const showDisputeForm = ref(false);
const disputeForm = useForm({
  issue_description: '',
  proof_image: null
});

const selectedDisputeImage = ref([]);

watch(() => props.show, (newVal) => {
  if (!newVal) {
    form.reset();
    form.clearErrors();
    selectedImage.value = [];
  }
});

const handleImageUpload = (file) => {
  form.proof_image = file;
  console.log('Image uploaded:', file);
};

const getDialogTitle = () => {
  switch (props.type) {
    case 'submit':
      return 'Submit Return Proof';
    case 'confirm':
      return 'Confirm Item Receipt';
    case 'finalize':
      return 'Complete Rental Transaction';
  }
};

const getDialogDescription = () => {
  switch (props.type) {
    case 'submit':
      return 'Please upload a photo of the item being returned.';
    case 'confirm':
      return 'Please upload a photo confirming you have received the item.';
    case 'finalize':
      return 'Before completing the transaction, please confirm that:';
  }
};

const handleSubmit = () => {
  const routes = {
    submit: 'rentals.submit-return',
    confirm: 'rentals.confirm-receipt',
    finalize: 'rentals.finalize-return'
  };

  // Set the proof image from selected image array
  form.proof_image = selectedImage.value[0];

  form.post(route(routes[props.type], props.rental.id), {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:show', false);
      selectedImage.value = [];  // Clear selected image
      form.reset();
    }
  });
};

// Add method to handle dispute submission
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

const isSubmitDisabled = computed(() => {
  if (props.type === 'finalize') return form.processing;
  return selectedImage.value.length === 0 || form.processing;
});

watchEffect(() => {
  console.log('Form state:', {
    proof_image: form.proof_image,
    type: props.type,
    disabled: (!form.proof_image && props.type !== 'finalize') || form.processing
  });
});
</script>

<template>
  <Dialog :open="show" @update:open="emit('update:show', $event)">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ getDialogTitle() }}</DialogTitle>
        <DialogDescription>{{ getDialogDescription() }}</DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <template v-if="type === 'finalize'">
          <div class="space-y-4">
            <!-- Existing checklist -->
            <ul class="space-y-2 text-sm">
              <li class="flex items-start gap-2">
                <span class="text-primary">•</span>
                <span>You have received and inspected the item</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-primary">•</span>
                <span>The item is in acceptable condition</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-primary">•</span>
                <span>There are no outstanding issues to resolve</span>
              </li>
            </ul>

            <!-- Add Dispute Section -->
            <div class="pt-4 border-t">
              <div v-if="!showDisputeForm">
                <Button 
                  type="button" 
                  variant="outline" 
                  @click="showDisputeForm = true"
                  class="w-full text-destructive hover:text-destructive"
                >
                  Raise Dispute
                </Button>
                <p class="text-xs text-muted-foreground mt-2 text-center">
                  Click here if there are issues with the item or rental transaction
                </p>
              </div>

              <div v-else class="space-y-4">
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
                    @click="showDisputeForm = false"
                  >
                    Cancel
                  </Button>
                  <Button
                    type="button"
                    variant="destructive"
                    @click="handleDisputeSubmit"
                    :disabled="!disputeForm.issue_description || 
                               selectedDisputeImage.length === 0 || 
                               disputeForm.processing"
                  >
                    Submit Dispute
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </template>

        <template v-else>
          <ImageUpload
            :maxFiles="1"
            @images="selectedImage = $event"
            :error="form.errors.proof_image"
            class="w-full aspect-video"
          />
        </template>

        <!-- Update action buttons -->
        <div class="flex justify-end gap-2" v-if="!showDisputeForm">
          <Button
            type="button"
            variant="outline"
            @click="emit('update:show', false)"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            :disabled="isSubmitDisabled"
          >
            {{ props.type === 'finalize' ? 'Complete Transaction' : 'Submit' }}
          </Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
