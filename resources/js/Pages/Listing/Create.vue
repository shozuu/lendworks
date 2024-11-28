<script setup>
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import ImageUpload from "@/Components/ImageUpload.vue";
import Textarea from "@/Components/ui/textarea/Textarea.vue";
import { useForm as useVeeForm } from "vee-validate";
import { useForm as useInertiaForm } from "@inertiajs/vue3";
import { toTypedSchema } from "@vee-validate/zod";
import { vAutoAnimate } from "@formkit/auto-animate";
import * as z from "zod";
import {
	FormControl,
	FormDescription,
	FormField,
	FormItem,
	FormLabel,
	FormMessage,
} from "@/components/ui/form";
import {
	Select,
	SelectContent,
	SelectGroup,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select";

const formSchema = toTypedSchema(
	z.object({
		title: z.string().min(5).max(100),
		desc: z.string().min(10).max(1000),
		category: z.preprocess((value) => parseInt(value, 10), z.number().int()),
		value: z
			.number()
			.positive()
			.refine((val) => Number.isInteger(val), {
				message: "Value must be a whole number (no decimals).",
			}),
		price: z
			.number()
			.positive()
			.refine((val) => Number.isInteger(val), {
				message: "Price must be a whole number (no decimals).",
			}),
		images: z.preprocess(
			(value) => {
				if (Array.isArray(value) && value.length > 0) {
					const notFileInstance = value.every((item) => !(item instanceof File));
					if (notFileInstance) {
						value = value.map((item) => item.file);
					}
				}
				return value;
			},
			z
				.array(
					z
						.instanceof(File)
						.refine((file) => file.size <= 3 * 1024 * 1024, {
							message: "Each image must be less than 3MB",
						})
						.refine(
							(file) =>
								["image/jpeg", "image/png", "image/jpg", "image/webp"].includes(
									file.type
								),
							{
								message: "Only JPEG, PNG, JPG, and WEBP images are allowed.",
							}
						)
				)
				.min(1, { message: "At least one image is required." })
		),
	})
);

const form = useVeeForm({
	validationSchema: formSchema,
});

const onSubmit = form.handleSubmit((values) => {
	const inertiaForm = useInertiaForm({
		title: values.title,
		desc: values.desc,
		category_id: values.category,
		value: values.value,
		price: values.price,
		images: values.images,
	});
	inertiaForm.post(route("listing.store"));
});

defineProps({ categories: Array });
</script>

<template>
	<Head title="| New Listing" />

	<div class="mb-10">
		<h2 class="text-2xl font-semibold tracking-tight">Create Listing</h2>
	</div>

	<form @submit="onSubmit" class="space-y-4" enctype="multipart/form-data">
		<FormField v-slot="{ componentField }" name="title">
			<FormItem v-auto-animate>
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
			<FormItem v-auto-animate>
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
			<FormItem v-auto-animate>
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
			<FormItem v-auto-animate>
				<FormLabel>Value</FormLabel>
				<FormDescription> The estimated value of your tool. (In Php)</FormDescription>
				<FormControl>
					<Input type="number" v-bind="componentField" />
				</FormControl>
				<FormMessage />
			</FormItem>
		</FormField>

		<FormField v-slot="{ componentField }" name="price">
			<FormItem v-auto-animate>
				<FormLabel>Daily Rental Rate</FormLabel>
				<FormDescription> Set your daily rental price. (In Php)</FormDescription>
				<FormControl>
					<Input type="number" v-bind="componentField" />
				</FormControl>
				<FormMessage />
			</FormItem>
		</FormField>

		<FormField v-slot="{ componentField }" name="images">
			<FormItem v-auto-animate>
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
