<script setup>
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import ListingImages from "@/Components/ListingImages.vue";
import { Card, CardContent } from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import Separator from "@/Components/ui/separator/Separator.vue";
import { CalendarDays } from "lucide-vue-next";
import Button from "@/Components/ui/button/Button.vue";
import { format } from "date-fns";
import RentalForm from "@/Components/RentalForm.vue";
import { Link } from "@inertiajs/vue3";
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from "@/components/ui/dialog";
import { useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";
import ItemCard from "@/Components/ItemCard.vue";

const props = defineProps({ listing: Object, relatedListings: Object });

const showDeleteDialog = ref(false);
const deleteForm = useForm({});

const handleDelete = () => {
	deleteForm.delete(route("listing.destroy", props.listing.id), {
		onSuccess: () => {
			showDeleteDialog.value = false;
			// prevent back navigation to deleted listing
			router.visit(route("my-rentals"), {
				preserveState: false,
				preserveScroll: false,
				replace: true,
				onFinish: () => {
					// clear the browser's entry for the deleted page
					window.history.replaceState(null, "", route("my-rentals"));
				},
			});
		},
	});
};
</script>

<template>
	<Head :title="`| Rent ${listing.title}`" />

	<Breadcrumbs
		:currentPage="listing.title"
		:category="listing.category.name"
		class="lg:flex hidden mb-10"
	/>

	<h2 class="mb-6 text-2xl font-semibold tracking-tight">
		{{ listing.title }}
	</h2>

	<div class="lg:grid-cols-2 grid items-start grid-cols-1 gap-10">
		<!-- first column -->

		<div class="grid gap-6">
			<!-- images -->
			<div class="mb-10">
				<ListingImages
					:images="
						listing.images.length
							? listing.images
							: ['/storage/images/listing/default.png']
					"
				/>
			</div>

			<!-- details -->
			<div class="space-y-1">
				<h2 class="text-lg font-semibold tracking-tight">Details</h2>

				<div class="text-muted-foreground">
					{{ listing.desc }}
				</div>
			</div>

			<Separator class="my-4" />

			<!-- cancellation terms -->
			<div class="space-y-1">
				<h2 class="text-lg font-semibold tracking-tight">Cancellation Terms</h2>

				<div class="space-y-2">
					<div class="text-muted-foreground">
						<span class="font-semibold">Full Refund:</span> If the renter cancels well in
						advance (e.g., 48-72 hours before the rental period begins).
					</div>
					<div class="text-muted-foreground">
						<span class="font-semibold">Partial refund (50%):</span> If the renter cancels
						closer to the rental date (e.g., 24-48 hours before).
					</div>
					<div class="text-muted-foreground">
						<span class="font-semibold">No refund:</span> If the renter cancels last
						minute (e.g., less than 24 hours before the rental period starts) or after the
						rental period has begun.
					</div>
				</div>
			</div>

			<Separator class="my-4" />

			<!-- location -->
			<div class="space-y-1">
				<h2 class="text-lg font-semibold tracking-tight">Listing Location</h2>

				<div class="text-muted-foreground">
					<!-- {{ listing.location }} -->
					Baliwasan, ZC
				</div>
			</div>

			<Separator class="my-4" />

			<!-- user info -->
			<Card>
				<CardContent
					class="sm:flex-row sm:items-center sm:justify-between flex flex-col gap-4 p-4 text-sm"
				>
					<div class="flex items-center gap-4">
						<Avatar>
							<AvatarImage src="https://picsum.photos/200" />
							<AvatarFallback>AT</AvatarFallback>
						</Avatar>

						<div>
							<h4 class="font-semibold">Listed By {{ listing.user.name }}</h4>

							<div class="flex items-center mt-2">
								<CalendarDays class="opacity-70 w-4 h-4 mr-2" />
								<span class="text-muted-foreground text-xs">
									Joined {{ format(new Date(listing.user.created_at), "MMMM yyyy") }}
								</span>
							</div>
						</div>
					</div>

					<div
						v-if="listing.user.id === $page.props.auth.user?.id"
						class="sm:w-auto grid w-full grid-cols-2 gap-2"
					>
						<Link :href="route('listing.edit', listing.id)">
							<Button variant="outline" class="w-full">Edit</Button>
						</Link>

						<Dialog v-model:open="showDeleteDialog">
							<DialogTrigger asChild>
								<Button variant="destructive" class="w-full">Delete</Button>
							</DialogTrigger>
							<DialogContent>
								<DialogHeader>
									<DialogTitle>Delete Listing</DialogTitle>
									<DialogDescription>
										Are you sure you want to delete this listing? This action cannot be
										undone.
									</DialogDescription>
								</DialogHeader>
								<DialogFooter>
									<Button variant="outline" @click="showDeleteDialog = false"
										>Cancel</Button
									>
									<Button
										variant="destructive"
										:disabled="deleteForm.processing"
										@click="handleDelete"
									>
										{{ deleteForm.processing ? "Deleting..." : "Delete" }}
									</Button>
								</DialogFooter>
							</DialogContent>
						</Dialog>
					</div>

					<Link v-else href="" class="sm:w-auto w-full">
						<Button class="sm:w-auto w-full">Message</Button>
					</Link>
				</CardContent>
			</Card>
		</div>

		<!-- second column -->
		<RentalForm :listing="listing" class="lg:min-w-96" />
	</div>

	<Separator class="my-10" />

	<!-- related listings -->
	<div class="space-y-6">
		<div class="space-y-1">
			<h2 class="text-lg font-semibold tracking-tight">Similar Tools</h2>
			<p class="text-muted-foreground">Other tools you might be interested in</p>
		</div>

		<div v-if="relatedListings?.length" class="sm:grid-cols-2 lg:grid-cols-4 grid gap-6">
			<div v-for="listing in relatedListings" :key="listing.id">
				<ItemCard :listing="listing" />
			</div>
		</div>
		<div v-else class="text-muted-foreground">No similar tools found.</div>
	</div>
</template>
