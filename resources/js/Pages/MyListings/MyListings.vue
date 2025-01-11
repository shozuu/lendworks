<script setup>
import ListingCard from "@/Components/ListingCard.vue";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

const props = defineProps({
	listings: Array,
	rejectionReasons: Array,
});

const form = useForm({});

const toggleAvailability = (listing) => {
	form.patch(route("listing.toggle-availability", listing.id));
};
</script>

<template>
	<Head title="| My Listings" />
	<div class="space-y-6">
		<div class="flex items-center justify-between">
			<div class="space-y-1">
				<h2 class="text-2xl font-semibold tracking-tight">My Listings</h2>
				<p class="text-muted-foreground">Manage your listed items</p>
			</div>

			<Link :href="route('listing.create')">
				<Button size="lg">Create Listing</Button>
			</Link>
		</div>

		<Tabs defaultValue="all" class="w-full">
			<TabsList>
				<TabsTrigger value="all">All Listings</TabsTrigger>
				<TabsTrigger value="available">Available</TabsTrigger>
				<TabsTrigger value="unavailable">Unavailable</TabsTrigger>
				<TabsTrigger value="pending">Pending Approval</TabsTrigger>
			</TabsList>

			<TabsContent value="all">
				<div v-if="listings.length" class="space-y-4">
					<ListingCard
						v-for="listing in listings"
						:key="listing.id"
						:listing="listing"
						:rejection-reasons="rejectionReasons"
						@toggleAvailability="toggleAvailability"
					/>
				</div>
				<div v-else class="text-muted-foreground text-center py-10">
					You haven't listed any items yet.
				</div>
			</TabsContent>

			<TabsContent value="available">
				<div class="space-y-4">
					<ListingCard
						v-for="listing in listings.filter(
							(l) => l.status === 'approved' && l.is_available
						)"
						:key="listing.id"
						:listing="listing"
						:rejection-reasons="rejectionReasons"
						@toggleAvailability="toggleAvailability"
					/>
				</div>
			</TabsContent>

			<TabsContent value="unavailable">
				<div class="space-y-4">
					<ListingCard
						v-for="listing in listings.filter((l) => l.approved && !l.is_available)"
						:key="listing.id"
						:listing="listing"
						:rejection-reasons="rejectionReasons"
						@toggleAvailability="toggleAvailability"
					/>
				</div>
			</TabsContent>

			<TabsContent value="pending">
				<div class="space-y-4">
					<ListingCard
						v-for="listing in listings.filter((l) => l.status !== 'approved')"
						:key="listing.id"
						:listing="listing"
						:rejection-reasons="rejectionReasons"
						@toggleAvailability="toggleAvailability"
					/>
				</div>
			</TabsContent>
		</Tabs>
	</div>
</template>
