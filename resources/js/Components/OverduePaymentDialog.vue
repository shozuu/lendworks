<script setup>
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import { ref } from "vue";
import { Button } from "@/components/ui/button";
import { formatNumber, formatDateTime } from "@/lib/formatters";
import { Textarea } from "@/components/ui/textarea";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    show: Boolean,
    payment: Object,
});

const emit = defineEmits(['update:show']);

const rejectionFeedback = ref('');
const feedbackError = ref('');

const handleVerify = () => {
    router.post(route('admin.payments.verify', props.payment.id), {}, {
        onSuccess: () => {
            emit('update:show', false);
        },
    });
};

const handleReject = () => {
    if (rejectionFeedback.value.trim().length < 10) {
        feedbackError.value = 'Feedback must be at least 10 characters long';
        return;
    }

    router.post(route('admin.payments.reject', props.payment.id), {
        feedback: rejectionFeedback.value
    }, {
        onSuccess: () => {
            emit('update:show', false);
            rejectionFeedback.value = '';
            feedbackError.value = '';
        },
    });
};
</script>

<template>
    <Dialog :open="show" @update:open="$emit('update:show', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Verify Overdue Payment</DialogTitle>
                <DialogDescription>
                    Review overdue payment details and proof of payment
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="bg-muted p-4 rounded-lg space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-muted-foreground">Overdue Amount:</span>
                        <span class="font-medium">{{ formatNumber(payment?.amount) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-muted-foreground">Reference Number:</span>
                        <span>{{ payment?.reference_number }}</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Payment Proof</label>
                    <img 
                        :src="`/storage/${payment?.payment_proof_path}`"
                        class="w-full rounded-lg border"
                        alt="Payment Proof"
                    />
                </div>

                <!-- Rejection Feedback Input -->
                <div v-if="!payment?.verified_at" class="space-y-2">
                    <Textarea
                        v-model="rejectionFeedback"
                        placeholder="Enter rejection feedback (minimum 10 characters)"
                        :error="feedbackError"
                    />
                    <p v-if="feedbackError" class="text-destructive text-sm">
                        {{ feedbackError }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div v-if="!payment?.verified_at" class="flex justify-end gap-2">
                    <Button variant="destructive" @click="handleReject">
                        Reject Payment
                    </Button>
                    <Button @click="handleVerify">
                        Verify Payment
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
