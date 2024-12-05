<script setup>
import ListingCard from "@/Components/ListingCard.vue";

defineProps({
    listings: Array
});

defineEmits(['toggleAvailability']);
</script>

<template>
    <div v-if="listings.length" class="space-y-4">
        <ListingCard
            v-for="listing in listings"
            :key="listing.id"
            :listing="listing"
            @toggleAvailability="$emit('toggleAvailability', $event)"
        />
    </div>
    <div v-else class="text-muted-foreground py-10 text-center">
        No listings found
    </div>
</template>