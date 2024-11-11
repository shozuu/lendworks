<script setup>
    import { Button } from '@/components/ui/button'
    import { Checkbox } from '@/components/ui/checkbox'
    import TextLink from '../../Components/TextLink.vue';
    import InputField from '../../Components/InputField.vue';
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
            <h1 class="text-3xl font-bold mb-2">Welcome Back</h1>
            <p>Don't have an account?
                <TextLink routeName="register" label="Register"/>
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
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

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Checkbox name="remember" v-model:checked="form.remember" class="w-5 h-5" id="remember"/>
                    <label
                        for="remember"
                        class="text-sm text-text cursor-pointer select-none"
                    >
                        Remember Me
                    </label>
                </div>

                <TextLink class="text-sm" routeName="home" label="Forgot Password?" />
            </div>

            <p v-if="form.errors.failed" class="text-warning text-sm text-center mt-1">{{ form.errors.failed }}</p>

            <Button 
                :disabled="form.processing"
                class="w-full rounded-lg"
                size="lg">
                Login
            </Button>
        </form>
    </div>
</template>