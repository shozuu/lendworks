<script setup>
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
  show: Boolean,
  rental: Object,
  type: {
    type: String,
    validator: value => ['submit', 'confirm'].includes(value)
  }
});

const emit = defineEmits(['update:show']);

const form = useForm({
  proof_image: null
});

const imagePreview = ref(null);
const fileInput = ref(null);

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.proof_image = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

const handleSubmit = () => {
  const route = props.type === 'submit' 
    ? 'rentals.submit-return'
    : 'rentals.confirm-return';

  form.post(route(route, props.rental.id), {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:show', false);
      form.reset();
      imagePreview.value = null;
    },
  });
};

const handleClose = () => {
  emit('update:show', false);
  form.reset();
  imagePreview.value = null;
};
</script>

<template>
  <Dialog :open="show" @update:open="handleClose">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>
          {{ type === 'submit' ? 'Submit Return Proof' : 'Confirm Return Receipt' }}
        </DialogTitle>
      </DialogHeader>

      <div class="space-y-4">
        <div 
          class="flex items-center justify-center border-2 border-dashed rounded-lg p-4"
          :class="[imagePreview ? 'border-primary' : 'border-muted-foreground']"
        >
          <div v-if="!imagePreview" class="text-center">
            <p class="text-muted-foreground text-sm">
              Click to upload or drag and drop
            </p>
            <p class="text-xs text-muted-foreground mt-1">
              PNG, JPG or JPEG (max. 5MB)
            </p>
          </div>
          <img 
            v-else 
            :src="imagePreview" 
            class="max-h-[300px] object-contain"
          />
          <input
            ref="fileInput"
            type="file"
            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
            accept="image/*"
            @change="handleFileChange"
          />
        </div>

        <div class="flex justify-end gap-2">
          <Button variant="outline" @click="handleClose">
            Cancel
          </Button>
          <Button 
            @click="handleSubmit"
            :disabled="!form.proof_image || form.processing"
          >
            Submit
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
