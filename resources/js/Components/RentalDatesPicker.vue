<script setup>
import { Button } from "@/components/ui/button";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { RangeCalendar } from "@/components/ui/range-calendar";
import { cn } from "../lib/utils";
import { CalendarDate, DateFormatter, getLocalTimeZone } from "@internationalized/date";
import { Calendar as CalendarIcon } from "lucide-vue-next";
import { ref, computed, watch } from "vue";
import { format } from "date-fns";

const props = defineProps({
	modelValue: {
		type: Object,
		required: true,
		default: () => ({ start: null, end: null }),
	},
	minDate: {
		type: Date,
		required: false,
		default: () => new Date(),
	},
	disabledDates: {
		type: Array,
		default: () => [],
	},
});

const emit = defineEmits(["update:modelValue"]);

// Convert JS Date to CalendarDate for the RangeCalendar component
const today = new CalendarDate(
	props.minDate.getFullYear(),
	props.minDate.getMonth() + 1,
	props.minDate.getDate()
);

// Internal value for the calendar
const calendarValue = ref({
	start: null,
	end: null,
});

// Watch calendar value changes and emit updates
watch(
	calendarValue,
	(newValue) => {
		if (newValue.start && newValue.end) {
				// convert to Date objects and emit
			const start = new Date(newValue.start.toDate(getLocalTimeZone()));
			const end = new Date(newValue.end.toDate(getLocalTimeZone()));
			emit("update:modelValue", { start, end });
		}
	},
	{ deep: true }
);

// Format dates for display
const dateRange = computed(() => {
	const df = new DateFormatter("en-US", { dateStyle: "medium" });

	if (!calendarValue.value.start) return "Select dates";
	if (!calendarValue.value.end)
		return df.format(calendarValue.value.start.toDate(getLocalTimeZone()));

	return `${df.format(
		calendarValue.value.start.toDate(getLocalTimeZone())
	)} - ${df.format(calendarValue.value.end.toDate(getLocalTimeZone()))}`;
});
</script>

<template>
	<Popover>
		<PopoverTrigger as-child>
			<Button
				variant="outline"
				:class="
					cn(
						'w-full justify-start text-left font-normal',
						!calendarValue.start && 'text-muted-foreground'
					)
				"
			>
				<CalendarIcon class="w-4 h-4 mr-2" />
				{{ dateRange }}
			</Button>
		</PopoverTrigger>

		<PopoverContent class="w-auto p-0">
			<RangeCalendar
				v-model="calendarValue"
				:min-value="today"
				initial-focus
				:number-of-months="2"
				:is-date-unavailable="
					(date) => {
						return disabledDates?.includes(
							format(date.toDate(getLocalTimeZone()), 'yyyy-MM-dd')
						);
					}
				"
			/>
		</PopoverContent>
	</Popover>
</template>
