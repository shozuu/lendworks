<script setup>
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
	Dialog,
	DialogContent,
	DialogFooter,
	DialogHeader,
	DialogTitle,
	DialogDescription,
} from "@/components/ui/dialog";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
import { formatNumber } from "@/lib/formatters";

const props = defineProps({
	listing: {
		type: Object,
		required: true,
	},
});

const emit = defineEmits(["approve", "reject", "takedown"]);

const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const showTakedownDialog = ref(false);
const rejectionReason = ref('');

const getStatusBadge = () => {
	switch (props.listing.status) {
		case 'approved':
			return { variant: 'success', label: 'Approved' };
		case 'rejected':
			return { variant: 'destructive', label: 'Rejected' };
		default:
			return { variant: 'warning', label: 'Pending' };
	}
};

const viewDetails = (listingId) => {
	router.get(route("admin.listings.show", listingId));
};
</script>

<template>
	<Card>
		<CardContent class="flex gap-6 p-6">
			<!-- Image -->
			<div class="shrink-0 sm:h-32 sm:w-32 w-24 h-24 overflow-hidden rounded-md">
				<img
					:src="
						props.listing.images?.[0]
							? `/storage/${props.listing.images[0].image_path}`
							: '/storage/images/listing/default.png'
					"
					:alt="props.listing.title"
					class="object-cover w-full h-full"
				/>
			</div>

			<!-- Content -->
			<div class="flex-1 space-y-3 flex flex-col">
				<div class="flex flex-col justify-between gap-1">
					<div class="flex items-center gap-2">
						<h3 class="font-semibold">{{ props.listing.title }}</h3>
						<Badge :variant="getStatusBadge().variant">
							{{ getStatusBadge().label }}
						</Badge>
					</div>
					<p class="text-muted-foreground max-w-md text-sm line-clamp-1">
						{{ props.listing.desc }}
					</p>
				</div>

				<div>
					<p>
						Value:
						<span class="text-muted-foreground">{{
							formatNumber(props.listing.value)
						}}</span>
					</p>
					<p>
						Daily Rate:
						<span class="text-muted-foreground">{{
							formatNumber(props.listing.price)
						}}</span>
					</p>
					<p>
						Listed by:
						<span class="text-muted-foreground">{{ props.listing.user?.name }}</span>
					</p>
					<p class="line-clamp-1">
						Location:
						<span class="text-muted-foreground"
							>{{ props.listing.location?.address }},
							{{ props.listing.location?.city }}</span
						>
					</p>
				</div>

				<div class="flex gap-2 self-end">
					<Button variant="outline" size="sm" @click="viewDetails(props.listing.id)">
						View Details
					</Button>
					<template v-if="props.listing.status === 'pending'">
						<Button variant="default" size="sm" @click="showApproveDialog = true">
							Approve
						</Button>
						<Button variant="destructive" size="sm" @click="showRejectDialog = true">
							Reject
						</Button>
					</template>
					<Button 
						v-else-if="props.listing.status === 'approved'"
						variant="destructive" 
						size="sm" 
						@click="showTakedownDialog = true"
					>
						Take Down
					</Button>
				</div>
			</div>
		</CardContent>
	</Card>

	<!-- Approve Dialog -->
	<Dialog v-model:open="showApproveDialog">
		<DialogContent>
			<DialogHeader>
				<DialogTitle>Approve Listing</DialogTitle>
				<DialogDescription>
					Are you sure you want to approve this listing? It will become visible to all
					users.
				</DialogDescription>
			</DialogHeader>
			<DialogFooter>
				<Button variant="outline" @click="showApproveDialog = false">Cancel</Button>
				<Button
					@click="
						() => {
							$emit('approve', props.listing);
							showApproveDialog = false;
						}
					"
				>
					Approve
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>

	<!-- Reject Dialog -->
	<Dialog v-model:open="showRejectDialog">
		<DialogContent>
			<DialogHeader>
				<DialogTitle>Reject Listing</DialogTitle>
				<DialogDescription>
					Please provide a reason for rejecting this listing. This will be sent to the user.
				</DialogDescription>
			</DialogHeader>
			<div class="py-4">
				<textarea
					v-model="rejectionReason"
					class="w-full min-h-[100px] p-2 border rounded"
					placeholder="Enter rejection reason..."
				></textarea>
			</div>
			<DialogFooter>
				<Button variant="outline" @click="showRejectDialog = false">Cancel</Button>
				<Button
					variant="destructive"
					:disabled="!rejectionReason.trim()"
					@click="$emit('reject', { listing: props.listing, reason: rejectionReason })"
				>
					Reject
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>

	<!-- Add Takedown Dialog -->
	<Dialog v-model:open="showTakedownDialog">
		<DialogContent>
			<DialogHeader>
				<DialogTitle>Take Down Listing</DialogTitle>
				<DialogDescription>
					Please provide a reason for taking down this approved listing. This will be sent to the user.
				</DialogDescription>
			</DialogHeader>
			<div class="py-4">
				<textarea
					v-model="rejectionReason"
					class="w-full min-h-[100px] p-2 border rounded"
					placeholder="Enter takedown reason..."
				></textarea>
			</div>
			<DialogFooter>
				<Button variant="outline" @click="showTakedownDialog = false">Cancel</Button>
				<Button
					variant="destructive"
					:disabled="!rejectionReason.trim()"
					@click="$emit('takedown', { listing: props.listing, reason: rejectionReason })"
				>
					Take Down
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
