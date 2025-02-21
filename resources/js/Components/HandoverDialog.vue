<script setup>
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import ImageUpload from "@/Components/ImageUpload.vue";
import { ref } from "vue";

const props = defineProps({
    show: Boolean,
    rental: Object,
    type: {
        type: String,
        validator: (value) => ['handover', 'receive'].includes(value)
    }
});

const emit = defineEmits(['update:show']);

const form = useForm({
    proof_image: null
});

const selectedImage = ref([]);

const handleSubmit = () => {
    const endpoint = props.type === 'handover' 
        ? route('rentals.submit-handover', props.rental.id)
        : route('rentals.submit-receive', props.rental.id);

    form.proof_image = selectedImage.value[0];
    
    form.post(endpoint, {
        preserveScroll: true,
        onSuccess: () => {
            emit('update:show', false);
            selectedImage.value = [];
        },
    });
};
</script>

<template>
    <Dialog :open="show" @update:open="$emit('update:show', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ type === 'handover' ? 'Hand Over Item' : 'Receive Item' }}</DialogTitle>
                <DialogDescription>
                    Please provide a photo as proof of {{ type === 'handover' ? 'handover' : 'receiving the item' }}.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-4">
                <ImageUpload
                    :maxFiles="1"
                    @images="selectedImage = $event"
                    class="w-full"
                />

                <DialogFooter>
                    <Button 
                        type="submit" 
                        :disabled="selectedImage.length === 0 || form.processing"
                    >
                        {{ form.processing ? 'Submitting...' : 'Submit' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
