<script setup>
import { Dialog, DialogContent } from "@/components/ui/dialog";
import PickupDateSelector from "@/Components/PickupDateSelector.vue";

const props = defineProps({
	show: Boolean,
	rental: Object,
	userRole: String,
	lenderSchedules: Array,
});

const emits = defineEmits(["update:show"]);

const closeDialog = () => {
	emits("update:show", false);
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
		</DialogContent>
	</Dialog>
</template>
