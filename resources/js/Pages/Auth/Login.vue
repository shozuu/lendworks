<script setup>
    import Title from '../../Components/Title.vue';
    import TextLink from '../../Components/TextLink.vue';
    import InputField from '../../Components/InputField.vue';
    import PrimaryBtn from '../../Components/PrimaryBtn.vue';
    import CheckBox from '../../Components/CheckBox.vue';
    import { useForm } from '@inertiajs/vue3';
    import AuthLayout from '../../Layouts/AuthLayout.vue';

    defineOptions({ layout: AuthLayout })

    const form = useForm({
        email: "",
        password: "",
        remember: null,
    });

    const submit = () => {
        form.post(route('login'), {
            onSuccess: () => form.reset("password"),
        })
    }
</script>

<template>
    <Head title="| Login"/>
    <div class="mx-auto max-w-md mt-20">
        <div class="mb-8 text-center">
            <Title>Welcome Back</Title>
            <p>Don't have an account? 
                <TextLink routeName="register" label="Register"/> 
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
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

            <div class="flex items-center justify-between">
                <CheckBox name="remember" v-model="form.remember">Remember Me</CheckBox>

                <TextLink class="text-sm" routeName="home" label="Forgot Password?" />
            </div>

            <p v-if="form.errors.failed" class="text-warning text-sm text-center mt-1">{{ form.errors.failed }}</p>

            <PrimaryBtn 
                class="w-full text" 
                :disabled="form.processing"
                :style="{ color: '#ECECEC' }">Login
            </PrimaryBtn>
        </form>
    </div>
</template>