<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head } from "@inertiajs/vue3";
import { Card } from "@/components/ui/card";
import StatCard from "@/Components/StatCard.vue";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import TransactionCard from "./Components/TransactionCard.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";

defineOptions({ layout: AdminLayout });

const props = defineProps({
    transactions: Object,
    stats: Object,
    filters: Object
});

const periodOptions = [
    { value: '7', label: 'Last 7 days' },
    { value: '30', label: 'Last 30 days' },
    { value: '90', label: 'Last 90 days' }
];
</script>

<template>
    <Head title="| Admin - Rental Transactions" />

    <div class="space-y-6">
        <div class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-4">
            <div class="space-y-1">
                <h2 class="text-2xl font-semibold tracking-tight">Rental Transactions</h2>
                <p class="text-muted-foreground text-sm">Monitor rental activity and disputes</p>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <Select :model-value="filters.period" @update:model-value="period => router.get(route('admin.rental-transactions'), { ...filters, period })">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue :placeholder="periodOptions.find(o => o.value === filters.period)?.label" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="option in periodOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Stats -->
        <div class="md:grid-cols-3 grid gap-4">
            <StatCard label="Total Transactions" :value="stats.total" />
            <StatCard label="Rejected" :value="stats.rejected" />
            <StatCard label="Cancelled" :value="stats.cancelled" />
        </div>

        <!-- Transactions List -->
        <div v-if="transactions.data.length" class="space-y-4">
            <TransactionCard v-for="transaction in transactions.data" 
                :key="transaction.id" 
                :transaction="transaction" 
            />
            <PaginationLinks :paginator="transactions" />
        </div>
        <div v-else class="text-muted-foreground py-10 text-center">
            No transactions found
        </div>
    </div>
</template>
