<script setup>
import {
	Card,
	CardHeader,
	CardTitle,
	CardDescription,
	CardContent,
} from "@/components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { MailWarning, MailCheck } from "lucide-vue-next";
import InputField from "../../../Components/InputField.vue";
import { Button } from "@/components/ui/button";
import { router, useForm } from "@inertiajs/vue3";

const props = defineProps({
	user: Object,
	profile: Object,
	status: String,
});

const profile = props.profile || {};

// Only include editable fields in the form
const form = useForm({
	name: props.user.name,
	email: props.user.email,
});

const submit = () => {
	form.patch(route("profile.info"));
};

const verifyEmail = (e) => {
	router.post(
		route("verification.send"),
		{},
		{
			onStart: () => (e.target.disabled = true),
			onFinish: () => (e.target.disabled = false),
		}
	);
};

// Function to format dates from ISO format to local date
const formatDate = (dateString) => {
	if (!dateString) return "Not provided";
	return new Date(dateString).toLocaleDateString();
};

// Function to capitalize first letter of string
const capitalize = (str) => {
	if (!str) return "";
	return str.charAt(0).toUpperCase() + str.slice(1);
};
</script>

<template>
	<Card class="flex flex-col">
		<CardHeader>
			<CardTitle>Update Information</CardTitle>
			<CardDescription class="lg:w-2/5"
				>Update your account's username and email address.</CardDescription
			>
		</CardHeader>

		<CardContent class="flex-1 lg:w-3/5 lg:self-end">
			<p v-if="status" class="mb-2 text-sm text-green-700">{{ status }}</p>

			<Alert v-if="form.recentlySuccessful === true" class="mb-5" variant="success">
				<MailCheck class="shrink-0" />
				<div>
					<AlertTitle>Success!</AlertTitle>
					<AlertDescription>
						<p>Your credentials have been updated.</p>
					</AlertDescription>
				</div>
			</Alert>

			<Alert v-if="user.email_verified_at === null" variant="warning" class="mb-5">
				<MailWarning class="shrink-0" />
				<div>
					<AlertTitle>Reminder</AlertTitle>
					<AlertDescription>
						<p>Your email address is not verified.</p>
						<Button @click="verifyEmail" class="p-0 text-inherit" variant="link"
							>Verify Now</Button
						>
					</AlertDescription>
				</div>
			</Alert>

			<form @submit.prevent="submit" class="space-y-6">
				<!-- User account information - Editable -->
				<div class="space-y-4">
					<h3 class="text-lg font-medium">Account Information</h3>

					<InputField
						label="Username"
						icon="id-badge"
						v-model="form.name"
						:error="form.errors.name"
					/>

					<InputField
						label="Email"
						icon="envelope"
						v-model="form.email"
						:error="form.errors.email"
					/>

					<Button :disabled="form.processing" type="submit" class="mt-6">
						Save Changes
					</Button>
				</div>
			</form>
		</CardContent>

		<CardContent class="flex-1">
			<!-- Profile information - Read Only -->
			<div class="mt-8 pt-6 border-t">
				<h3 class="text-lg font-medium mb-4">Profile Information (Verified)</h3>

				<!-- Personal Information -->
				<div class="mb-6">
					<h4 class="text-md font-medium text-gray-700 mb-2">Personal Details</h4>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<p class="text-sm text-gray-500">Full Name</p>
							<p class="font-medium">
								{{ profile.first_name || "Not provided" }}
								{{ profile.middle_name || "" }} {{ profile.last_name || "" }}
							</p>
						</div>
						<div>
							<p class="text-sm text-gray-500">Birthdate</p>
							<p class="font-medium">{{ formatDate(profile.birthdate) }}</p>
						</div>
						<div>
							<p class="text-sm text-gray-500">Gender</p>
							<p class="font-medium">
								{{ capitalize(profile.gender) || "Not provided" }}
							</p>
						</div>
						<div>
							<p class="text-sm text-gray-500">Civil Status</p>
							<p class="font-medium">
								{{ capitalize(profile.civil_status) || "Not provided" }}
							</p>
						</div>
						<div>
							<p class="text-sm text-gray-500">Mobile Number</p>
							<p class="font-medium">{{ profile.mobile_number || "Not provided" }}</p>
						</div>
						<div>
							<p class="text-sm text-gray-500">Nationality</p>
							<p class="font-medium">{{ profile.nationality || "Not provided" }}</p>
						</div>
					</div>
				</div>

				<!-- Address Information -->
				<div class="mb-6">
					<h4 class="text-md font-medium text-gray-700 mb-2">Address Information</h4>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div class="md:col-span-2">
							<p class="text-sm text-gray-500">Street Address</p>
							<p class="font-medium">{{ profile.street_address || "Not provided" }}</p>
						</div>
						<div>
							<p class="text-sm text-gray-500">Barangay</p>
							<p class="font-medium">{{ profile.barangay || "Not provided" }}</p>
						</div>
						<div>
							<p class="text-sm text-gray-500">City</p>
							<p class="font-medium">{{ profile.city || "Not provided" }}</p>
						</div>
						<div>
							<p class="text-sm text-gray-500">Province</p>
							<p class="font-medium">{{ profile.province || "Not provided" }}</p>
						</div>
						<div>
							<p class="text-sm text-gray-500">Postal Code</p>
							<p class="font-medium">{{ profile.postal_code || "Not provided" }}</p>
						</div>
					</div>
				</div>

				<!-- ID Information -->
				<div>
					<h4 class="text-md font-medium text-gray-700 mb-2">ID Information</h4>
					<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
						<div>
							<p class="text-sm text-gray-500">Primary ID Type</p>
							<p class="font-medium">{{ profile.primary_id_type || "Not provided" }}</p>
						</div>
						<div>
							<p class="text-sm text-gray-500">Secondary ID Type</p>
							<p class="font-medium">{{ profile.secondary_id_type || "Not provided" }}</p>
						</div>
					</div>
				</div>
			</div>
		</CardContent>
	</Card>
</template>
