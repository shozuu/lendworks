<script setup>
import RentalCard from "@/Components/RentalCard.vue";
import PaginationLinks from "@/Components/PaginationLinks.vue";

defineProps({
    rentals: Array,
    showPagination: Boolean,
    paginator: Object
});
</script>

<template>
    <div class="space-y-6">
        <div v-if="rentals.length" class="space-y-4">
            <RentalCard
                v-for="rental in rentals"
                :key="rental.id"
                :rental="rental"
            />
            <PaginationLinks 
                v-if="showPagination" 
                :paginator="paginator" 
            />
        </div>
        <div v-else class="text-muted-foreground py-10 text-center">
            No rentals found
        </div>
    </div>
</template>