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

watch(() => props.show, (newVal) => {
  if (!newVal) {
    form.reset();
    form.clearErrors();
    selectedImage.value = [];
  }
});

const handleImageUpload = (file) => {
  form.proof_image = file;
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

  form.proof_image = selectedImage.value[0];

  form.post(route(routes[props.type], props.rental.id), {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:show', false);
      selectedImage.value = [];
      form.reset();
    }
  });
};

const isSubmitDisabled = computed(() => {
  if (props.type === 'finalize') return form.processing;
  return selectedImage.value.length === 0 || form.processing;
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
            :disabled="isSubmitDisabled"
          >
            {{ props.type === 'finalize' ? 'Complete Transaction' : 'Submit' }}
          </Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
