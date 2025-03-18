<script setup>
import TextLink from "../../Components/TextLink.vue";
import InputField from "../../Components/InputField.vue";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/vue3";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import TermsAndConditionsDialog from "../TermsAndConditionsDialog .vue";
import { ref } from "vue";

defineOptions({ layout: AuthLayout });

const form = useForm({
	name: "",
	email: "",
	password: "",
	password_confirmation: "",
});

const showTerms = ref(false);
const termsAccepted = ref(false);

const submit = () => {
	form.post(route("register"), {
		onSuccess: () => form.reset("password", "password_confirmation"),
	});
};
</script>

<template>
	<Head title="| Register" />
	<div class="mx-auto max-w-md mt-20">
		<div class="mb-8 text-center">
			<h1 class="text-3xl font-bold mb-2">Register a new account</h1>
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

			<p class="text-text text-sm flex items-center gap-1">
				By creating an account, you agree to our
				<button
					type="button"
					@click="showTerms = true"
					class="text-primary hover:underline inline-flex"
				>
					Terms and Conditions
				</button>
			</p>

			<div
				class="flex items-center gap-2 bg-muted/50 p-3 rounded-lg"
				:class="{ 'bg-emerald-100/50': termsAccepted }"
			>
				<div
					class="w-2 h-2 rounded-full"
					:class="termsAccepted ? 'bg-emerald-500' : 'bg-muted-foreground'"
				></div>
				<span class="text-sm" :class="termsAccepted ? 'text-emerald-700' : 'bg-muted/50'">
					{{
						termsAccepted
							? "Terms and conditions accepted"
							: "Please accept the terms and conditions"
					}}
				</span>
			</div>

			<Button
				:disabled="form.processing || !termsAccepted"
				class="w-full rounded-lg"
				size="lg"
			>
				Register
			</Button>
		</form>
	</div>

	<TermsAndConditionsDialog v-model:show="showTerms" @accept="termsAccepted = true" />
</template>
