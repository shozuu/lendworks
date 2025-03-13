<script setup>
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import ImageUpload from "@/Components/ImageUpload.vue";
import Textarea from "@/Components/ui/textarea/Textarea.vue";
import { formatNumber } from "@/lib/formatters";
import { calculateDailyRate, calculateDepositFee } from "@/lib/suggestRate";
import { watchEffect } from "vue";
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
	z
		.object({
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
			deposit_fee: z
				.number()
				.positive()
				.refine((val) => Number.isInteger(val), {
					message: "Deposit fee must be a whole number (no decimals).",
				}),
			quantity: z.number().int().min(1).max(100),
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
			location: z.string().min(1, "Location is required"),
			new_location: z.boolean().default(false),
			location_name: z.string().max(100).optional().nullable(),
			address: z.string().max(255).optional().nullable(),
			city: z.string().max(100).optional().nullable(),
			province: z.string().max(100).optional().nullable(),
			postal_code: z.string().max(20).optional().nullable(),
		})
		.superRefine((data, ctx) => {
			if (data.location === "new") {
				if (!data.location_name?.trim()) {
					ctx.addIssue({
						code: z.ZodIssueCode.custom,
						message: "Location name is required when adding a new location",
						path: ["location_name"],
					});
				}
				if (!data.address?.trim()) {
					ctx.addIssue({
						code: z.ZodIssueCode.custom,
						message: "Address is required when adding a new location",
						path: ["address"],
					});
				}
				if (!data.city?.trim()) {
					ctx.addIssue({
						code: z.ZodIssueCode.custom,
						message: "City is required when adding a new location",
						path: ["city"],
					});
				}
				if (!data.province?.trim()) {
					ctx.addIssue({
						code: z.ZodIssueCode.custom,
						message: "Province is required when adding a new location",
						path: ["province"],
					});
				}
				if (!data.postal_code?.trim()) {
					ctx.addIssue({
						code: z.ZodIssueCode.custom,
						message: "Postal code is required when adding a new location",
						path: ["postal_code"],
					});
				}
			}
		})
);

const props = defineProps({
	listing: Object,
	categories: Array,
	locations: Array,
});

const form = useVeeForm({
	validationSchema: formSchema,
	initialValues: {
		title: props.listing.title,
		desc: props.listing.desc,
		category: String(props.listing.category.id),
		value: props.listing.value,
		price: props.listing.price,
		deposit_fee: props.listing.deposit_fee,
		quantity: props.listing.quantity,
		images: [], // to be populated by ImageUpload component
		location: String(props.listing.location.id),
	},
});

const inertiaForm = useInertiaForm({
	title: "",
	desc: "",
	category_id: "",
	value: "",
	price: "",
	deposit_fee: "",
	quantity: "",
	images: [],
	_method: "PATCH", // simulate PATCH request
	location_id: "",
	new_location: false,
	location_name: "",
	address: "",
	city: "",
	province: "",
	postal_code: "",
});

const onSubmit = form.handleSubmit((values) => {
	inertiaForm.title = values.title;
	inertiaForm.desc = values.desc;
	inertiaForm.category_id = Number(values.category);
	inertiaForm.value = Number(values.value);
	inertiaForm.price = Number(values.price);
	inertiaForm.deposit_fee = Number(values.deposit_fee);
	inertiaForm.quantity = Number(values.quantity);
	inertiaForm.images = values.images;

	if (values.location === "new") {
		inertiaForm.new_location = true;
		inertiaForm.location_name = values.location_name;
		inertiaForm.address = values.address;
		inertiaForm.city = values.city;
		inertiaForm.province = values.province;
		inertiaForm.postal_code = values.postal_code;
		inertiaForm.location_id = null;
	} else {
		inertiaForm.new_location = false;
		inertiaForm.location_id = values.location;
	}

	inertiaForm.post(route("listing.update", props.listing.id));
});

let dailyRate;
let depositFee;
watchEffect(() => {
	dailyRate = calculateDailyRate(form.values.value);
	depositFee = calculateDepositFee(form.values.value);
});
</script>

<template>
	<Head title="| Edit Listing" />

	<div class="mb-10">
		<h2 class="text-2xl font-semibold tracking-tight">Edit Listing</h2>
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
				<FormDescription v-if="form.values.value > 0">
					We suggest a daily rental price between
					{{ formatNumber(dailyRate.minRate) }} and {{ formatNumber(dailyRate.maxRate) }}
					based on your item's value
				</FormDescription>
			</FormItem>
		</FormField>

		<FormField v-slot="{ componentField }" name="deposit_fee">
			<FormItem v-auto-animate>
				<FormLabel>Security Deposit</FormLabel>
				<FormDescription>
					Set the security deposit amount renters must pay. This helps protect your item
					against damage or loss.
				</FormDescription>
				<FormControl>
					<Input type="number" v-bind="componentField" />
				</FormControl>
				<FormMessage />
				<FormDescription v-if="form.values.value > 0">
					We suggest a security deposit between
					{{ formatNumber(depositFee.minRate) }} and
					{{ formatNumber(depositFee.maxRate) }}
					based on your item's value
				</FormDescription>
			</FormItem>
		</FormField>

		<!-- Add Quantity Field -->
		<FormField v-slot="{ componentField }" name="quantity">
			<FormItem v-auto-animate>
				<FormLabel>Quantity Available</FormLabel>
				<FormDescription
					>How many units of this item do you have available for rent?</FormDescription
				>
				<FormControl>
					<Input type="number" min="1" max="100" v-bind="componentField" />
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
						:initial-files="props.listing.images"
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

		<FormField v-slot="{ componentField }" name="location">
			<FormItem v-auto-animate>
				<FormLabel>Location</FormLabel>
				<FormDescription>Choose where this item is located.</FormDescription>
				<FormControl>
					<Select
						v-bind="componentField"
						@update:model-value="form.setFieldValue('new_location', false)"
					>
						<SelectTrigger>
							<SelectValue placeholder="Select a location" />
						</SelectTrigger>
						<SelectContent>
							<SelectGroup>
								<SelectItem
									v-for="location in locations"
									:key="location.id"
									:value="String(location.id)"
								>
									{{ location.name }} - {{ location.address }}
								</SelectItem>
								<SelectItem value="new">+ Add New Location</SelectItem>
							</SelectGroup>
						</SelectContent>
					</Select>
				</FormControl>
				<FormMessage />
			</FormItem>
		</FormField>

		<!-- New Location Form -->
		<div v-if="form.values.location === 'new'" class="space-y-4">
			<FormField v-slot="{ componentField }" name="location_name">
				<FormItem v-auto-animate>
					<FormLabel>Location Name</FormLabel>
					<FormControl>
						<Input
							type="text"
							v-bind="componentField"
							placeholder="Home, Workshop, etc."
						/>
					</FormControl>
					<FormMessage />
				</FormItem>
			</FormField>

			<FormField v-slot="{ componentField }" name="address">
				<FormItem v-auto-animate>
					<FormLabel>Address</FormLabel>
					<FormControl>
						<Input type="text" v-bind="componentField" />
					</FormControl>
					<FormMessage />
				</FormItem>
			</FormField>

			<div class="grid grid-cols-2 gap-4">
				<FormField v-slot="{ componentField }" name="city">
					<FormItem v-auto-animate>
						<FormLabel>City</FormLabel>
						<FormControl>
							<Input type="text" v-bind="componentField" />
						</FormControl>
						<FormMessage />
					</FormItem>
				</FormField>

				<FormField v-slot="{ componentField }" name="province">
					<FormItem v-auto-animate>
						<FormLabel>Province</FormLabel>
						<FormControl>
							<Input type="text" v-bind="componentField" />
						</FormControl>
						<FormMessage />
					</FormItem>
				</FormField>
			</div>

			<FormField v-slot="{ componentField }" name="postal_code">
				<FormItem v-auto-animate>
					<FormLabel>Postal Code</FormLabel>
					<FormControl>
						<Input type="text" v-bind="componentField" />
					</FormControl>
					<FormMessage />
				</FormItem>
			</FormField>
		</div>

		<Button type="submit" :disabled="inertiaForm.processing">
			{{ inertiaForm.processing ? "Updating..." : "Update" }}
		</Button>
	</form>
</template>
