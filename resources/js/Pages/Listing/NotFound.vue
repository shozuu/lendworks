<script setup>
import ItemCard from '@/Components/ItemCard.vue'; 
import { Link } from '@inertiajs/vue3';

defineProps({
  message: String,
  suggestions: Array
});
</script>

<template>
    <div class="space-y-8">
        <div class="space-y-4">
            <h1 class="text-2xl font-semibold">Listing Not Found</h1>
            <p class="text-muted-foreground">{{ message }}</p>
            <Link 
                href="/"
                class="text-primary hover:underline inline-block"
            >
                Browse other listings
            </Link>
        </div>

        <div v-if="suggestions?.length" class="space-y-4">
            <h2 class="text-xl font-semibold">You might be interested in:</h2>
            <div class="sm:grid-cols-2 lg:grid-cols-4 grid gap-6">
                <ItemCard 
                    v-for="listing in suggestions"
                    :key="listing.id"
                    :listing="listing"
                />
            </div>
        </div>
    </div>
</template>