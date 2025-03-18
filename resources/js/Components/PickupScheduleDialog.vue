<script setup>
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button"; // Add this import
import PickupDateSelector from "@/Components/PickupDateSelector.vue";
import { useForm } from "@inertiajs/vue3"; // Update this import
import { computed } from "vue";

const props = defineProps({
	show: Boolean,
	rental: Object,
	userRole: String,
	lenderSchedules: Array,
});

const emits = defineEmits(["update:show", "schedule-confirmed"]);

const closeDialog = () => {
	emits("update:show", false);
};

const confirmForm = useForm({});

const selectedSchedule = computed(() =>
	props.rental.pickup_schedules?.find((s) => s.is_selected)
);

const handleConfirmSchedule = () => {
	confirmForm.patch(
		route("pickup-schedules.confirm", {
			rental: props.rental.id,
		}),
		{
			preserveScroll: true,
			preserveState: false, // Ensure fresh data load
			onSuccess: () => {
				emits("schedule-confirmed");
				emits("update:show", false);
			},
			onError: (errors) => {
				console.error("Schedule confirmation failed:", errors);
			},
		}
	);
};
</script>

<template>
	<Dialog :open="show" @update:open="closeDialog">
		<DialogContent class="max-w-2xl">
			<div class="py-4">
				<PickupDateSelector
					:rental="rental"
					:userRole="userRole"
					:lenderSchedules="lenderSchedules"
					@schedule-selected="closeDialog"
				/>
			</div>
			<div
				v-if="userRole === 'lender' && selectedSchedule && !selectedSchedule.is_confirmed"
				class="mt-4"
			>
				<Button
					class="w-full"
					@click="handleConfirmSchedule"
					:disabled="confirmForm.processing"
				>
					{{ confirmForm.processing ? "Confirming..." : "Confirm Schedule" }}
				</Button>
			</div>
		</DialogContent>
	</Dialog>
</template>
