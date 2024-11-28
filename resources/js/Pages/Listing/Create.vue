<script setup>
import { ref, watch, watchEffect } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "vee-validate";
import { toTypedSchema } from "@vee-validate/zod";
import * as z from "zod";
import calculateDailyRate from "../../suggestRate";
import {
	FormControl,
	FormDescription,
	FormField,
	FormItem,
	FormLabel,
	FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import Textarea from "@/Components/ui/textarea/Textarea.vue";
import ImageUpload from "@/Components/ImageUpload.vue";
import {
	Select,
	SelectContent,
	SelectGroup,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";
import { Button } from "@/components/ui/button";

const formSchema = toTypedSchema(
	z.object({
		title: z.string().min(5).max(100),
		desc: z.string().min(10).max(1000),
		category: z.preprocess((value) => parseInt(value, 10), z.number().int()),
		value: z.number().positive(),
		price: z.number().positive(),
		images: z
			.array(
				z
					.instanceof(File)
					.refine((file) => file.size <= 3 * 1024 * 1024, {
						message: "Each image must be less than 3MB",
					})
					.refine(
						(file) => ["image/jpeg", "image/png", "image/jpg"].includes(file.type),
						{
							message: "Only JPEG, PNG, and JPG images are allowed.",
						}
					)
			)
			.min(1, { message: "At least one image is required." }),
	})
);

const form = useForm({
	validationSchema: formSchema,
});

const minRate = ref(null);
const maxRate = ref(null);

const onSubmit = form.handleSubmit((values) => {
	Inertia.post(route("listing.store"), values);
});

let dailyRate;
watchEffect(() => {
	dailyRate = calculateDailyRate(form.values.value);
});

const props = defineProps({ categories: Array });
</script>

<template>
	<Head title="| New Listing" />

	<div class="mb-10">
		<h2 class="text-2xl font-semibold tracking-tight">Create Listing</h2>
	</div>

	<form @submit="onSubmit" class="space-y-4" enctype="multipart/form-data">
		<FormField v-slot="{ componentField }" name="title">
			<FormItem>
				<FormLabel>Title</FormLabel>
				<FormDescription>
					A short, clear name for your listing (e.g., "Bosche Cordless 18v Drill ").
				</FormDescription>
				<FormControl>
					<Input type="text" v-bind="componentField" />
				</FormControl>
				<FormMessage />
			</FormItem>
		</FormField>

		<FormField v-slot="{ componentField }" name="desc">
			<FormItem>
				<FormLabel>Description</FormLabel>
				<FormDescription>
					Describe your tool, its features, and condition.
				</FormDescription>
				<FormControl>
					<Textarea v-bind="componentField" />
				</FormControl>
				<FormMessage />
			</FormItem>
		</FormField>

		<FormField v-slot="{ componentField }" name="category">
			<FormItem>
				<FormLabel>Category</FormLabel>
				<FormDescription> Choose the category that fits your listing. </FormDescription>
				<Select v-bind="componentField">
					<FormControl>
						<SelectTrigger>
							<SelectValue placeholder="Select a category" />
						</SelectTrigger>
					</FormControl>
					<SelectContent>
						<SelectGroup>
							<SelectItem
								v-for="(category, id) in categories"
								:key="category.id"
								:value="String(category.id)"
							>
								{{ category.name }}
							</SelectItem>
						</SelectGroup>
					</SelectContent>
				</Select>
				<FormMessage />
			</FormItem>
		</FormField>

		<FormField v-slot="{ componentField }" name="value">
			<FormItem>
				<FormLabel>Value</FormLabel>
				<FormDescription> The estimated value of your tool. </FormDescription>
				<FormControl>
					<Input type="number" v-bind="componentField" />
				</FormControl>
				<FormMessage />
			</FormItem>
		</FormField>

		<!-- <FormField
			v-if="form.values.value && minRate !== null && maxRate !== null"
			name="suggestedRate"
		>
			<FormItem>
				<FormLabel>Suggested Daily Rate</FormLabel>
				<FormDescription>
					The estimated suggested daily rate range for your tool based on its value.
				</FormDescription>
				<FormControl>
					<p class="text-gray-700">Suggested Daily Rate:</p>
					<p class="font-semibold">
						₱{{ minRate.toFixed(2) }} - ₱{{ maxRate.toFixed(2) }}
					</p>
				</FormControl>
				<FormMessage />
			</FormItem>
		</FormField> -->

		<FormField v-slot="{ componentField }" name="price">
			<FormItem>
				<FormLabel>Daily Rental Rate</FormLabel>
				<FormDescription> Set your daily rental price. </FormDescription>
				<FormControl>
					<Input type="number" v-bind="componentField" />
				</FormControl>
				<FormMessage />
				<FormDescription v-if="form.values.value > 0">
					We suggest a daily rental price between ₱{{ dailyRate.minRate }} and ₱{{
						dailyRate.maxRate
					}}
					based on your item's value
				</FormDescription>
			</FormItem>
		</FormField>

		<FormField v-slot="{ componentField }" name="images">
			<FormItem>
				<FormLabel>Images</FormLabel>
				<FormDescription>
					Upload clear images of your tool. Preferably in landscape format (4:3).
				</FormDescription>
				<FormControl>
					<ImageUpload
						v-bind="componentField"
						@images="
							(imagesArray) => {
								form.setFieldValue('images', imagesArray);
							}
						"
					/>
				</FormControl>
				<FormMessage />
			</FormItem>
		</FormField>

		<Button type="submit" class=""> Submit </Button>
	</form>
</template>
