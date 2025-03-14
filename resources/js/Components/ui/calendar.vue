<script setup>
import { ArrowLeft, ArrowRight } from 'lucide-vue-next';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { ref, computed, defineExpose } from 'vue';

const props = defineProps({
  modelValue: {
    type: Date,
    required: false,
  },
  disabledDates: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['update:model-value']);

const currentMonth = ref(props.modelValue ? new Date(props.modelValue) : new Date());

// Array of month names
const months = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December"
];

// Array of weekday names
const weekDays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

const calendar = computed(() => {
  const year = currentMonth.value.getFullYear();
  const month = currentMonth.value.getMonth();
  
  // First day of the month
  const firstDay = new Date(year, month, 1);
  // Last day of the month
  const lastDay = new Date(year, month + 1, 0);
  
  // Array to hold all days
  const days = [];
  
  // Add padding days from previous month
  let startPadding = firstDay.getDay();
  for (let i = startPadding - 1; i >= 0; i--) {
    days.push({
      date: new Date(year, month, -i),
      isCurrentMonth: false
    });
  }
  
  // Add all days of current month
  for (let i = 1; i <= lastDay.getDate(); i++) {
    const date = new Date(year, month, i);
    days.push({
      date,
      isCurrentMonth: true,
      isSelected: props.modelValue && isSameDay(date, props.modelValue),
      isDisabled: isDateDisabled(date)
    });
  }
  
  // Add padding days from next month
  const endPadding = 42 - days.length; // 6 rows * 7 days = 42
  for (let i = 1; i <= endPadding; i++) {
    days.push({
      date: new Date(year, month + 1, i),
      isCurrentMonth: false
    });
  }
  
  return days;
});

function isDateDisabled(date) {
  if (!props.disabledDates) return false;
  
  const { before, after } = props.disabledDates;
  
  if (before && date < new Date(before)) return true;
  if (after && date > new Date(after)) return true;
  
  return false;
}

function isSameDay(date1, date2) {
  return date1.getFullYear() === date2.getFullYear() &&
         date1.getMonth() === date2.getMonth() &&
         date1.getDate() === date2.getDate();
}

function previousMonth() {
  currentMonth.value = new Date(
    currentMonth.value.getFullYear(),
    currentMonth.value.getMonth() - 1
  );
}

function nextMonth() {
  currentMonth.value = new Date(
    currentMonth.value.getFullYear(),
    currentMonth.value.getMonth() + 1
  );
}

function selectDate(date) {
  if (isDateDisabled(date)) return;
  emit('update:model-value', date);
}
</script>

<template>
  <div class="p-3 space-y-4">
    <!-- Calendar header with month/year and navigation -->
    <div class="flex items-center justify-between">
      <Button 
        variant="ghost" 
        size="icon"
        @click="previousMonth"
      >
        <ChevronLeft class="h-4 w-4" />
      </Button>
      <div class="font-semibold">
        {{ months[currentMonth.getMonth()] }} {{ currentMonth.getFullYear() }}
      </div>
      <Button 
        variant="ghost" 
        size="icon"
        @click="nextMonth"
      >
        <ChevronRight class="h-4 w-4" />
      </Button>
    </div>

    <!-- Calendar grid -->
    <div class="grid grid-cols-7 gap-1">
      <!-- Weekday headers -->
      <div 
        v-for="day in weekDays" 
        :key="day"
        class="text-center text-sm font-medium text-muted-foreground h-9 flex items-center justify-center"
      >
        {{ day }}
      </div>

      <!-- Calendar days -->
      <button
        v-for="{ date, isCurrentMonth, isSelected, isDisabled } in calendar"
        :key="date"
        type="button"
        @click="selectDate(date)"
        :disabled="isDisabled"
        class="h-9 w-9 rounded-md flex items-center justify-center text-sm transition-colors"
        :class="{
          'text-muted-foreground': !isCurrentMonth,
          'hover:bg-accent': !isDisabled && isCurrentMonth,
          'bg-primary text-primary-foreground': isSelected,
          'opacity-50 cursor-not-allowed': isDisabled
        }"
      >
        {{ date.getDate() }}
      </button>
    </div>
  </div>
</template>

<script>
export const Calendar = {
  name: 'Calendar',
  inheritAttrs: true
}
</script>
