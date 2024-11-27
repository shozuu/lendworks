<template>
	<div class="container mx-fixed p-6 min-h-[500px]">
		<!-- Header Section -->
		<div class="text-center mb-8">
			<h2 class="text-lg font-semibold tracking-tight">
				Renting and Listing Tools Made Easy
			</h2>

			<p class="text-sm text-muted-foreground mt-2">
				LendWorks makes it easy to borrow tools or earn by renting yours—all in a simple,
				secure process.
			</p>
		</div>

		<div
			class="flex justify-center gap-1 mb-8 border w-[230px] rounded-lg p-1.5 bg-background mx-auto"
		>
			<button
				@click="toggleView('rent')"
				:class="{
					'bg-primary text-primary-foreground shadow-md': currentView === 'rent',
					'text-secondary-foreground text-sm': currentView !== 'rent',
				}"
				class="px-9 p-1 rounded-lg text-md font-semibold tracking-tight"
			>
				Rent
			</button>
			<button
				@click="toggleView('lend')"
				:class="{
					'bg-primary text-primary-foreground shadow-md': currentView === 'lend',
					'text-secondary-foreground text-sm': currentView !== 'lend',
				}"
				class="px-9 p-1 rounded-lg text-md font-semibold tracking-tight"
			>
				Lend
			</button>
		</div>

		<!-- Conditional Rendering of Cards -->
		<div
			v-if="currentView === 'rent'"
			class="flex flex-col items-center gap-8 rounded-lg"
		>
			<!-- Grid Section -->
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-9">
				<div class="card" v-for="card in rentCards" :key="card.id">
					<div
						class="icon-container mb-4 bg-background border w-[55px] p-1.5 rounded-lg mx-auto"
					>
						<i :class="card.icon" class="text-3xl" style="color: #af1010"></i>
					</div>
					<h3 class="text-md font-semibold tracking-tight mb-2">{{ card.title }}</h3>
					<p class="text-sm text-muted-foreground mb-4">{{ card.description }}</p>
				</div>
			</div>

			<button class="bg-primary font-semibold text-white py-2 px-11 rounded-lg">
				Rent Now
			</button>
		</div>

		<div v-if="currentView === 'lend'" class="flex flex-col items-center gap-8">
			<!-- Grid Section -->
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
				<div class="card" v-for="card in lendCards" :key="card.id">
					<div
						class="icon-container mb-4 bg-background border w-[55px] p-1.5 rounded-lg mx-auto"
					>
						<i :class="card.icon" class="text-3xl" style="color: #af1010"></i>
					</div>

					<h3 class="text-md font-semibold tracking-tight mb-2">{{ card.title }}</h3>
					<p class="text-sm text-muted-foreground mb-4">{{ card.description }}</p>
				</div>
			</div>

			<button class="bg-primary font-semibold text-white py-2 px-11 rounded-lg">
				Lend Now
			</button>
		</div>
	</div>
</template>

<script>
export default {
	data() {
		return {
			currentView: "rent", // Initial view is 'rent'
			rentCards: [
				{
					id: 1,
					title: "Browse and Choose ",
					description:
						"Explore a wide range of tools near you by category or search. Compare ratings, prices, and availability to find the best fit for your project.",
					icon: "fas fa-magnifying-glass",
				},
				{
					id: 2,
					title: "Book and Pay",
					description:
						"Securely reserve the tool through LendWorks, with payment held in escrow until the rental is complete. You'll receive a confirmation with the tool's pickup details.",
					icon: "fas fa-credit-card",
				},
				{
					id: 3,
					title: "Pick Up and Return",
					description:
						"Meet the owner to collect the tool at a designated location. After using it, return the tool as agreed and leave a review.",
					icon: "fa-solid fa-handshake",
				},
			],
			lendCards: [
				{
					id: 1,
					title: "List your Tool",
					description:
						"Create a listing with your tool's details, photos, and rental price. Set terms and pick-up location for renters.",
					icon: "fas fa-toolbox",
				},
				{
					id: 2,
					title: "Confirm Rentals",
					description:
						"Receive and review rental requests with ease. Accept bookings that work for you, with LendWorks handling secure payments using an escrow payment method.",
					icon: "fas fa-check-circle",
				},
				{
					id: 3,
					title: "Meet and Earn",
					description:
						"Arrange a handoff with the renter, and after the tool is returned, receive payment directly to your account. Leave feedback on the rental experience.",
					icon: "fas fa-money-check-alt",
				},
			],
		};
	},
	methods: {
		toggleView(view) {
			this.currentView = view;
		},
	},
};
</script>

<style scoped>
.container {
	font-family: "Arial", sans-serif;
	background-color: hsl(var(--muted));
}

.card {
	background-color: var(--card);
	color: var(--card-foreground);
	padding: 20px;
	width: 100%;
	max-width: 250px;
	border-radius: 8px;
	text-align: center;
	transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.bg-primary-light {
	background-color: #e53e3e;
}

button {
	transition: background-color 0.3s ease, transform 0.3s ease;
}
</style>
