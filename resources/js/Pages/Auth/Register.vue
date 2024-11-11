<script setup>
    import TextLink from '../../Components/TextLink.vue';
    import InputField from '../../Components/InputField.vue';
    import { Button } from '@/components/ui/button'
    import { useForm } from '@inertiajs/vue3';
    import AuthLayout from '../../Layouts/AuthLayout.vue';

    defineOptions({ layout: AuthLayout })

    const form = useForm({
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
    });

    const submit = () => {
        form.post(route('register'), {
            onSuccess: () => form.reset("password", "password_confirmation"),
        })
    }
</script>

<template>
    <Head title="| Register"/>
    <div class="mx-auto max-w-md mt-20">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold mb-2">Register a new account</h1>
            <p>Already have an account?
                <TextLink routeName="login" label="Login"/>
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <InputField
                label="Name"
                icon="id-badge"
                v-model="form.name"
                placeholder="Full Name"
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
                type="password" icon="key"
                v-model="form.password_confirmation"
                placeholder="must be at least 8 characters"
                :error="form.errors.password"
            />

            <p class="text-text text-sm">By creating an account, you agree to our
                <TextLink routeName="home" label="Terms of Service"/> and
                <TextLink routeName="home" label="Privacy Policy"/>.
            </p>

            <Button 
                :disabled="form.processing"
                class="w-full rounded-lg"
                size="lg">
                Register
            </Button>
        </form>
    </div>
</template>
