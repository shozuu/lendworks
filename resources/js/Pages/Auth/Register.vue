<script setup>
    import Title from '../../Components/Title.vue';
    import TextLink from '../../Components/TextLink.vue';
    import InputField from '../../Components/InputField.vue';
    import PrimaryBtn from '../../Components/PrimaryBtn.vue';
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
            <Title>Register a new account</Title>
            <p>Already have an account?
                <TextLink routeName="login" label="Login"/>
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <InputField
                label="Name"
                icon="id-badge"
                v-model="form.name"
                :error="form.errors.name"
            />

            <InputField
                label="Email"
                icon="at"
                v-model="form.email"
                :error="form.errors.email"
            />

            <InputField
                label="Password"
                type="password"
                icon="key"
                v-model="form.password"
                :error="form.errors.password"
            />

            <InputField
                label="Confirm Password"
                type="password" icon="key"
                v-model="form.password_confirmation"
                :error="form.errors.password"
            />

            <p class="text-text text-sm">By creating an account, you agree to our
                <TextLink routeName="home" label="Terms of Service"/> and
                <TextLink routeName="home" label="Privacy Policy"/>.
            </p>

            <PrimaryBtn
                class="w-full text"
                :disabled="form.processing"
                :style="{ color: '#ECECEC' }">Register
            </PrimaryBtn>
        </form>
    </div>
</template>
