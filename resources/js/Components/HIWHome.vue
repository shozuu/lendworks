<script setup>
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/Components/ui/tabs";
import {
	Card,
	CardHeader,
	CardTitle,
	CardDescription,
	CardContent,
	CardFooter,
} from "@/Components/ui/card";
import { Button } from "@/components/ui/button";
import {
	Search,
	CreditCard,
	Handshake,
	Drill,
	CheckCircle,
	PhilippinePeso,
} from "lucide-vue-next";
</script>

<template>
	<div class="py-12">
		<div class="mb-10 text-center">
			<h2 class="mb-2 text-3xl font-bold">Renting and Listing Tools Made Easy</h2>
			<p class="text-muted-foreground">
				A simple and secure platform to rent or list tools in your community.
			</p>
		</div>

		<Tabs default-value="rent" class="w-full">
			<TabsList class="grid w-full grid-cols-2">
				<TabsTrigger value="rent" class="text-lg">Rent Tools</TabsTrigger>
				<TabsTrigger value="lend" class="text-lg">Lend Tools</TabsTrigger>
			</TabsList>

			<TabsContent value="rent">
				<Card class="py-4 md:p-0 md:min-h-[460px] lg:min-h-[350px] flex">
					<CardContent class="flex flex-col justify-between pt-12">
						<div class="md:grid-cols-3 grid gap-10">
							<div class="space-y-4">
								<div class="flex flex-col items-center gap-3 text-center">
									<Search class="text-primary w-10 h-10" />
									<div>
										<h3 class="font-semibold">Browse and Choose</h3>
										<p class="text-muted-foreground">
											Find nearby tools by category or search. Compare options and select
											what fits your needs.
										</p>
									</div>
								</div>
							</div>
							<div class="space-y-4">
								<div class="flex flex-col items-center gap-3 text-center">
									<CreditCard class="text-primary w-10 h-10" />
									<div>
										<h3 class="font-semibold">Book and Pay</h3>
										<p class="text-muted-foreground">
											Reserve tools with secure escrow payments. Get instant confirmation
											and pickup details.
										</p>
									</div>
								</div>
							</div>
							<div class="space-y-4">
								<div class="flex flex-col items-center gap-3 text-center">
									<Handshake class="text-primary w-10 h-10" />
									<div>
										<h3 class="font-semibold">Pick Up and Return</h3>
										<p class="text-muted-foreground">
											Meet the owner, collect your tool, and return it when done. Share
											your experience.
										</p>
									</div>
								</div>
							</div>
						</div>
						<CardFooter class="flex justify-center mt-8">
							<Button size="lg">Rent Now</Button>
						</CardFooter>
					</CardContent>
				</Card>
			</TabsContent>

			<TabsContent value="lend">
				<Card class="py-4 md:p-0 md:min-h-[460px] lg:min-h-[350px] flex">
					<CardContent class="flex flex-col justify-between pt-12">
						<div class="md:grid-cols-3 grid gap-6">
							<div class="space-y-4">
								<div class="flex flex-col items-center gap-3 text-center">
									<Drill class="text-primary w-10 h-10" />
									<div>
										<h3 class="font-semibold">List your Tool</h3>
										<p class="text-muted-foreground">
											Add your tool with photos and pricing. Set your terms and location.
										</p>
									</div>
								</div>
							</div>
							<div class="space-y-4">
								<div class="flex flex-col items-center gap-3 text-center">
									<CheckCircle class="text-primary w-10 h-10" />
									<div>
										<h3 class="font-semibold">Confirm Rentals</h3>
										<p class="text-muted-foreground">
											Review and accept bookings that suit your schedule. We handle the
											payments.
										</p>
									</div>
								</div>
							</div>
							<div class="space-y-4">
								<div class="flex flex-col items-center gap-3 text-center">
									<PhilippinePeso class="text-primary w-10 h-10" />
									<div>
										<h3 class="font-semibold">Meet and Earn</h3>
										<p class="text-muted-foreground">
											Hand over your tool, get it back, and receive your payment. Rate
											your experience.
										</p>
									</div>
								</div>
							</div>
						</div>
						<CardFooter class="flex justify-center mt-8">
							<Button size="lg">List Now</Button>
						</CardFooter>
					</CardContent>
				</Card>
			</TabsContent>
		</Tabs>
	</div>
</template>
