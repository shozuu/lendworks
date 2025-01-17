<script setup>
import AdminLayout from "../../Layouts/AdminLayout.vue";
import AdminListingCard from "@/Components/AdminListingCard.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
import {
	Dialog,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogDescription,
	DialogFooter,
} from "@/components/ui/dialog";

defineOptions({ layout: AdminLayout });

const props = defineProps({
	user: Object,
});

// Add handlers for approve/reject actions
const handleApprove = (listing) => {
	router.patch(route("admin.listings.approve", listing.id), {}, { preserveScroll: true });
};

const handleReject = (listing) => {
	router.patch(route("admin.listings.reject", listing.id), {}, { preserveScroll: true });
};

const showSuspendDialog = ref(false);
const showActivateDialog = ref(false);

const handleSuspend = () => {
	router.patch(
		route("admin.users.suspend", props.user.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => {
				showSuspendDialog.value = false;
			},
		}
	);
};

const handleActivate = () => {
	router.patch(
		route("admin.users.activate", props.user.id),
		{},
		{
			preserveScroll: true,
			onSuccess: () => {
				showActivateDialog.value = false;
			},
		}
	);
};
</script>

<template>
	<Head :title="`| Admin - User Details: ${user.name}`" />

	<div class="space-y-6">
		<!-- User Info Card -->
		<Card>
			<CardHeader>
				<CardTitle>User Information</CardTitle>
			</CardHeader>
			<CardContent class="space-y-4">
				<div class="grid md:grid-cols-2 gap-4">
					<div>
						<h3 class="font-semibold">{{ user.name }}</h3>
						<p class="text-sm text-muted-foreground">{{ user.email }}</p>
						<div class="mt-2">
							<Badge :variant="user.status === 'active' ? 'success' : 'destructive'">
								{{ user.status }}
							</Badge>
						</div>
					</div>
					<div class="text-sm">
						<p>Member since: {{ new Date(user.created_at).toLocaleDateString() }}</p>
						<p>Total Listings: {{ user.listings.length }}</p>
					</div>
					<div class="col-span-2 flex gap-2 justify-end">
						<Button
							v-if="user.status === 'active'"
							variant="destructive"
							@click="showSuspendDialog = true"
						>
							Suspend User
						</Button>
						<Button
							v-if="user.status === 'suspended'"
							variant="default"
							@click="showActivateDialog = true"
						>
							Activate User
						</Button>
					</div>
				</div>
			</CardContent>
		</Card>

		<!-- User's Listings -->
		<div class="space-y-4">
			<h3 class="text-lg font-semibold">User's Listings</h3>
			<div v-if="user.listings.length" class="space-y-4">
				<AdminListingCard
					v-for="listing in user.listings"
					:key="listing.id"
					:listing="listing"
					@approve="handleApprove"
					@reject="handleReject"
				/>
			</div>
			<p v-else class="text-muted-foreground text-center py-8">
				This user has no listings.
			</p>
		</div>

		<!-- Suspend Dialog -->
		<Dialog v-model:open="showSuspendDialog">
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Suspend User</DialogTitle>
					<DialogDescription>
						Are you sure you want to suspend this user? They will not be able to create
						listings or perform any transactions.
					</DialogDescription>
				</DialogHeader>
				<DialogFooter>
					<Button variant="outline" @click="showSuspendDialog = false">Cancel</Button>
					<Button variant="destructive" @click="handleSuspend">Suspend</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>

		<!-- Activate Dialog -->
		<Dialog v-model:open="showActivateDialog">
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Activate User</DialogTitle>
					<DialogDescription>
						Are you sure you want to activate this user? They will be able to resume
						normal account activities.
					</DialogDescription>
				</DialogHeader>
				<DialogFooter>
					<Button variant="outline" @click="showActivateDialog = false">Cancel</Button>
					<Button @click="handleActivate">Activate</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	</div>
</template>
