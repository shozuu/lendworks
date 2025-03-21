<script setup>
import TextLink from "../../Components/TextLink.vue";
import InputField from "../../Components/InputField.vue";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import TermsAndConditionsDialog from "../TermsAndConditionsDialog .vue";
import { ref, computed } from "vue";
import { Checkbox } from "@/Components/ui/checkbox";

defineOptions({ layout: AuthLayout });

const form = useForm({
	name: "",
	email: "",
	password: "",
	password_confirmation: "",
});

const showTerms = ref(false);
const termsAccepted = ref(false);

const isDisabled = computed(() => {
	return (
		!form.name ||
		!form.email ||
		!form.password ||
		!form.password_confirmation ||
		!termsAccepted.value ||
		form.processing
	);
});

const submit = () => {
	form.post(route("register"), {
		onSuccess: () => form.reset("password", "password_confirmation"),
	});
};
</script>

<template>
	<Head title="| Register" />
	<div class="max-w-md mx-auto mt-20">
		<div class="mb-8 text-center">
			<h1 class="mb-2 text-3xl font-bold">Register a new account</h1>
			<p>
				Already have an account?
				<TextLink routeName="login" label="Login" />
			</p>
		</div>

		<form @submit.prevent="submit" class="space-y-6">
			<InputField
				label="Name"
				icon="id-badge"
				v-model="form.name"
				placeholder="Username"
				:error="form.errors.name"
			/>

			<InputField
				label="Email"
				icon="envelope"
				v-model="form.email"
				placeholder="email@example.com"
				:error="form.errors.email"
			/>

			<InputField
				label="Password"
				type="password"
				icon="key"
				v-model="form.password"
				placeholder="must be at least 8 characters"
				:error="form.errors.password"
			/>

			<InputField
				label="Confirm Password"
				type="password"
				icon="key"
				v-model="form.password_confirmation"
				placeholder="must be at least 8 characters"
				:error="form.errors.password"
			/>

			<div class="flex items-start gap-2 mt-4 mb-4">
				<Checkbox id="rental-terms" v-model:checked="termsAccepted" class="mt-1" />
				<label for="rental-terms" class="text-sm cursor-pointer">
					By renting, I have read and agree to the
					<button
						type="button"
						@click="showTerms = true"
						class="text-primary hover:underline inline-flex font-medium"
					>
						Terms and Conditions
					</button>
				</label>
			</div>

			<Button :disabled="isDisabled" class="w-full rounded-lg" size="lg">
				Register
			</Button>
		</form>
	</div>

	<TermsAndConditionsDialog v-model:show="showTerms" @accept="termsAccepted = true" />
</template>
