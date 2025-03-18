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
const isVerifying = ref(false);
const isRejecting = ref(false);

const handleVerify = () => {
    isVerifying.value = true;
    router.post(route('admin.payments.verify', props.payment.id), {}, {
        preserveScroll: true, // Add this option
        onSuccess: () => {
            emit('update:show', false);
            isVerifying.value = false;
            // Force a page refresh to show updated data
            router.reload();
        },
        onError: (error) => {
            isVerifying.value = false;
            console.error('Verification failed:', error);
        },
    });
};

const handleReject = () => {
    if (rejectionFeedback.value.trim().length < 10) {
        feedbackError.value = 'Feedback must be at least 10 characters long';
        return;
    }

    isRejecting.value = true;
    router.post(route('admin.payments.reject', props.payment.id), {
        feedback: rejectionFeedback.value
    }, {
        onSuccess: () => {
            emit('update:show', false);
            rejectionFeedback.value = '';
            feedbackError.value = '';
            isRejecting.value = false;
        },
        onError: () => {
            isRejecting.value = false;
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
                        placeholder="Explain why you're rejecting this payment (minimum 10 characters)"
                        :error="feedbackError"
                        :disabled="isRejecting || isVerifying"
                    />
                    <p v-if="feedbackError" class="text-destructive text-sm">
                        {{ feedbackError }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div v-if="!payment?.verified_at" class="flex justify-end gap-2">
                    <Button 
                        variant="destructive" 
                        @click="handleReject"
                        :disabled="isRejecting || isVerifying"
                    >
                        {{ isRejecting ? 'Rejecting...' : 'Reject Payment' }}
                    </Button>
                    <Button 
                        @click="handleVerify"
                        :disabled="isRejecting || isVerifying"
                    >
                        {{ isVerifying ? 'Verifying...' : 'Verify Payment' }}
                    </Button>
                </div>

                <!-- Add status messages -->
                <div v-if="payment?.verified_at" class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
                    <div class="flex items-center gap-2 text-emerald-600">
                        <CheckCircleIcon class="h-5 w-5" />
                        <p class="font-medium">Payment Verified</p>
                    </div>
                    <p class="text-sm text-emerald-600 mt-1">
                        Verified on {{ formatDateTime(payment.verified_at) }}
                    </p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
