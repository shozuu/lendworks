<script setup>
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { formatDateTime } from "@/lib/formatters";
import { Calendar, Clock, AlertTriangle } from "lucide-vue-next";

const props = defineProps({
    rental: {
        type: Object,
        required: true
    }
});

const isActive = computed(() => true); // Always show as active
const isOverdue = computed(() => props.rental.is_overdue ?? false);

const formatDays = (days) => {
    return typeof days === 'number' ? `${days} days` : '0 days';
};

// Update the display values to persist after return initiation
const stats = computed(() => {
    const isReturnInitiated = props.rental.status.includes('return');

    return [{
        label: 'Rental Duration',
        value: formatDays(props.rental.rental_duration),
        icon: Clock,
        description: `${formatDateTime(props.rental.start_date, 'MMM D')} - ${formatDateTime(props.rental.end_date, 'MMM D, YYYY')}`
    },
    {
        label: isOverdue.value ? 'Overdue By' : 'Final Days Remaining',
        value: isOverdue.value 
            ? formatDays(props.rental.overdue_days)
            : formatDays(isReturnInitiated ? 0 : props.rental.remaining_days),
        icon: isOverdue.value ? AlertTriangle : Calendar,
        variant: isOverdue.value ? 'destructive' : 'default',
        description: isOverdue.value
            ? `Was due on ${formatDateTime(props.rental.end_date, 'MMM D, YYYY')}`
            : isReturnInitiated 
                ? `Return initiated on ${formatDateTime(props.rental.end_date, 'MMM D, YYYY')}`
                : `Due on ${formatDateTime(props.rental.end_date, 'MMM D, YYYY')}`
    }];
});

const showTracker = computed(() => {
    // Show tracker for all statuses except cancelled, rejected or completed states
    const hideStates = ['cancelled', 'rejected'];
    return !hideStates.includes(props.rental.status);
});
</script>

<template>
    <div v-if="showTracker" class="space-y-4">
        <Card>
            <CardHeader>
                <CardTitle>Rental Duration Tracker</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 md:grid-cols-2">
                    <div 
                        v-for="stat in stats" 
                        :key="stat.label"
                        class="space-y-2"
                    >
                        <div class="flex items-center gap-2">
                            <component 
                                :is="stat.icon" 
                                class="w-4 h-4"
                                :class="stat.variant === 'destructive' ? 'text-destructive' : 'text-muted-foreground'"
                            />
                            <h4 class="text-sm font-medium">{{ stat.label }}</h4>
                        </div>
                        <p 
                            class="text-2xl font-bold"
                            :class="stat.variant === 'destructive' ? 'text-destructive' : ''"
                        >
                            {{ stat.value }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ stat.description }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
