<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import ListingCard from "@/Components/ListingCard.vue";
import { format } from "date-fns";
import { formatNumber } from "@/lib/formatters";

defineOptions({
  layout: AdminLayout,
});

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
  pendingListings: {
    type: Array,
    required: true,
  },
  recentPayments: {
    type: Array,
    required: true,
  },
  revenueStats: {
    type: Object,
    required: true,
  }
});

const handleApproveListing = (listingId) => {
  router.patch(route('admin.listings.approve', listingId));
};

// Add this helper function
const formatDate = (dateString) => {
  if (!dateString) return 'Not submitted';
  return format(new Date(dateString), "MMM d, yyyy");
};
</script>

<template>
  <Head title="Admin Dashboard" />

  <!-- Stats Overview -->
  <div class="md:grid-cols-4 grid gap-4 mb-6">
    <Card>
      <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
        <CardTitle class="text-sm font-medium">Total Users</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">{{ stats.totalUsers }}</div>
      </CardContent>
    </Card>
    <Card>
      <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
        <CardTitle class="text-sm font-medium">Active Rentals</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">{{ stats.activeRentals }}</div>
      </CardContent>
    </Card>
    <Card>
      <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
        <CardTitle class="text-sm font-medium">Pending Payments</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">{{ stats.pendingPayments }}</div>
      </CardContent>
    </Card>
    <Card>
      <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
        <CardTitle class="text-sm font-medium">Monthly Revenue</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="text-2xl font-bold">{{ formatNumber(revenueStats.monthly) }}</div>
      </CardContent>
    </Card>
  </div>

  <!-- Pending Listings -->
  <Card class="mb-6">
    <CardHeader>
      <CardTitle>Pending Listings</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="space-y-4">
        <div v-for="listing in pendingListings" :key="listing.id" 
          class="flex items-center justify-between p-4 border rounded-lg">
          <div>
            <h3 class="font-semibold">{{ listing.title }}</h3>
            <p class="text-muted-foreground text-sm">by {{ listing.user.name }}</p>
          </div>
          <Button @click="handleApproveListing(listing.id)">
            Approve
          </Button>
        </div>
        <div v-if="!pendingListings.length" class="text-muted-foreground py-4 text-center">
          No pending listings to approve
        </div>
      </div>
    </CardContent>
  </Card>

  <!-- Recent Payments -->
  <Card>
    <CardHeader>
      <CardTitle>Recent Payment Submissions</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="space-y-4">
        <div v-for="payment in recentPayments" :key="payment.id" 
          class="flex items-center justify-between pb-4 border-b">
          <div>
            <p class="font-medium">{{ payment.renter.name }}</p>
            <p class="text-muted-foreground text-sm">{{ payment.listing.title }}</p>
          </div>
          <div class="text-right">
            <p class="font-medium">{{ formatNumber(payment.total_price) }}</p>
            <p class="text-muted-foreground text-sm">
              {{ formatDate(payment.payment_proof_submitted_at) }}
            </p>
          </div>
        </div>
        <div v-if="!recentPayments.length" class="text-muted-foreground py-4 text-center">
          No recent payment submissions
        </div>
      </div>
    </CardContent>
  </Card>
</template>
