<script setup>
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { ScrollArea } from "@/components/ui/scroll-area";
import { formatNumber } from "@/lib/formatters";
import { useForm as useVeeForm } from "vee-validate";
import { useForm as useInertiaForm } from "@inertiajs/vue3";
import { toTypedSchema } from "@vee-validate/zod";
import { vAutoAnimate } from "@formkit/auto-animate/vue";
import * as z from "zod";
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import ImageUpload from "@/Components/ImageUpload.vue";

const props = defineProps({
    show: Boolean,
    rental: Object,
});

const emit = defineEmits(["update:show"]);

// Use the same QR code path as regular payments
const qrCodePath = "/storage/app/public/images/payment/QR_Payment.jpg";

const formSchema = toTypedSchema(
    z.object({
        reference_number: z.string()
            .min(1, "Reference number is required")
            .max(50, "Reference number is too long"),
        payment_proof: z.preprocess(
            (value) => {
                if (Array.isArray(value) && value.length > 0) {
                    const notFileInstance = value.every((item) => !(item instanceof File));
                    if (notFileInstance) {
                        value = value.map((item) => item.file);
                    }
                }
                return value;
            },
            z.array(
                z.instanceof(File)
                    .refine((file) => file.size <= 3 * 1024 * 1024, {
                        message: "Image must be less than 3MB",
                    })
                    .refine(
                        (file) => ["image/jpeg", "image/png", "image/jpg", "image/webp"].includes(file.type),
                        { message: "Only JPEG, PNG, JPG, and WEBP images are allowed." }
                    )
            ).length(1, { message: "Payment proof screenshot is required" })
        ),
    })
);

const form = useVeeForm({
    validationSchema: formSchema,
});

const inertiaForm = useInertiaForm({
    reference_number: "",
    payment_proof: null,
});

const onSubmit = form.handleSubmit((values) => {
    inertiaForm.reference_number = values.reference_number;
    inertiaForm.payment_proof = values.payment_proof[0];

    inertiaForm.post(route("rentals.submit-overdue-payment", props.rental.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit("update:show", false);
        },
    });
});
</script>

<template>
    <Dialog :open="show" @update:open="$emit('update:show', $event)">
        <DialogContent class="sm:max-w-md flex flex-col max-h-[90vh]">
            <DialogHeader>
                <DialogTitle>Pay Overdue Fees</DialogTitle>
                <DialogDescription>
                    Please pay the overdue fees to proceed with the return process
                </DialogDescription>
            </DialogHeader>

            <ScrollArea class="flex-1 pr-2 overflow-y-auto">
                <div class="space-y-6">
                    <!-- Payment Amount -->
                    <div class="bg-muted p-4 rounded-lg">
                        <div class="flex items-center justify-between font-medium">
                            <span class="text-sm">Overdue Fee:</span>
                            <span class="text-lg">{{ formatNumber(rental.overdue_fee) }}</span>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="space-y-6 divide-y">
                        <!-- QR Code Method -->
                        <div class="space-y-4">
                            <h3 class="font-medium">Scan QR Code</h3>
                            <div class="flex justify-center">
                                <img
                                    :src="qrCodePath"
                                    alt="Payment QR Code"
                                    class="object-contain w-48 h-48 border rounded-lg"
                                />
                            </div>
                        </div>

                        <!-- Manual Input Method -->
                        <div class="pt-6 space-y-4">
                            <div class="space-y-2">
                                <h3 class="font-medium">GCash Details</h3>
                                <div class="bg-muted/50 p-3 space-y-1 rounded-md">
                                    <p class="text-sm">Name: AL**N BE****D T.</p>
                                    <p class="text-sm">Number: 09359447500</p>
                                </div>
                            </div>

                            <!-- Payment Form -->
                            <form @submit="onSubmit" class="space-y-4">
                                <FormField v-slot="{ componentField }" name="reference_number">
                                    <FormItem v-auto-animate>
                                        <FormLabel>GCash Reference Number</FormLabel>
                                        <FormControl>
                                            <Input
                                                type="text"
                                                placeholder="Enter your GCash reference number"
                                                v-bind="componentField"
                                            />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="payment_proof">
                                    <FormItem v-auto-animate>
                                        <FormLabel>Payment Screenshot</FormLabel>
                                        <FormControl>
                                            <ImageUpload
                                                v-bind="componentField"
                                                @images="(imagesArray) => {
                                                    form.setFieldValue('payment_proof', imagesArray);
                                                }"
                                            />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <Button type="submit" class="w-full" :disabled="inertiaForm.processing">
                                    {{ inertiaForm.processing ? "Submitting..." : "Submit Payment" }}
                                </Button>
                            </form>
                        </div>
                    </div>
                </div>
            </ScrollArea>
        </DialogContent>
    </Dialog>
</template>
