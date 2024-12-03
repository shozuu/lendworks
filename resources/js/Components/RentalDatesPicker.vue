<script setup>
import { Button } from "@/components/ui/button";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { RangeCalendar } from "@/components/ui/range-calendar";
import { cn } from "../lib/utils";
import { CalendarDate, DateFormatter, getLocalTimeZone } from "@internationalized/date";
import { Calendar as CalendarIcon } from "lucide-vue-next";
import { ref, watch } from "vue";

// Date formatter for displaying date ranges
const df = new DateFormatter("en-US", {
	dateStyle: "medium",
});

// Reactive value for the selected date range
const value = ref({
	start: "",
	end: "",
});

// Get today's date in CalendarDate format
const today = new CalendarDate(
	new Date().getFullYear(),
	new Date().getMonth() + 1,
	new Date().getDate()
);

const emit = defineEmits(["update-dates"]);

watch(value, (newValue) => {
	if (newValue.start && newValue.end) {
		const startDate = newValue.start.toDate("Asia/Manila");
		const endDate = newValue.end.toDate("Asia/Manila");

		emit(
			"update-dates",
			startDate.toISOString().split("T")[0],
			endDate.toISOString().split("T")[0]
		);
	}
});
</script>

<template>
	<Popover>
		<!-- Trigger Button -->
		<PopoverTrigger as-child>
			<Button
				variant="outline"
				:class="
					cn(
						'justify-start text-left font-normal',
						!value.start && 'text-muted-foreground'
					)
				"
			>
				<CalendarIcon class="w-4 h-4 mr-2" />
				<!-- If a start date is selected -->
				<template v-if="value.start">
					<!-- If an end date is also selected -->
					<template v-if="value.end">
						{{ df.format(value.start.toDate(getLocalTimeZone())) }} -
						{{ df.format(value.end.toDate(getLocalTimeZone())) }}
					</template>

					<!-- Only start date is selected -->
					<template v-else>
						{{ df.format(value.start.toDate(getLocalTimeZone())) }}
					</template>
				</template>
				<!-- No date selected -->
				<template v-else> Select Rental Dates </template>
			</Button>
		</PopoverTrigger>

		<!-- Calendar Popover -->
		<PopoverContent class="w-auto p-0">
			<RangeCalendar
				v-model="value"
				initial-focus
				:number-of-months="2"
				:min-value="today"
				@update:start-value="(startDate) => (value.start = startDate)"
				@update:end-value="(endDate) => (value.end = endDate)"
			/>
		</PopoverContent>
	</Popover>
</template>
