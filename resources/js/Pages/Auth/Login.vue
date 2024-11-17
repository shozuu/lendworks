<script setup>
    import { Button } from '@/components/ui/button'
    import { Checkbox } from '@/components/ui/checkbox'
    import { KeyRound  } from 'lucide-vue-next'
    import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
    import TextLink from '../../Components/TextLink.vue';
    import InputField from '../../Components/InputField.vue';
    import { useForm } from '@inertiajs/vue3';
    import AuthLayout from '../../Layouts/AuthLayout.vue';

    defineOptions({ layout: AuthLayout })
    defineProps({ status: String })

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
    <div class="max-w-md mx-auto mt-20">
        <div class="mb-8 text-center">
            <h1 class="mb-2 text-3xl font-bold">Welcome Back</h1>
            <p>Don't have an account?
                <TextLink routeName="register" label="Register"/>
            </p>
        </div>

        <Alert v-if="status" variant="success" class="mb-6">
            <KeyRound/>
            <div>
                <AlertTitle>Success!</AlertTitle>
                <AlertDescription>
                    {{ status }}
                </AlertDescription>
            </div>
        </Alert>

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
                        class="text-sm cursor-pointer select-none text-text"
                    >
                        Remember Me
                    </label>
                </div>

                <TextLink class="text-sm" routeName="password.request" label="Forgot Password?" />
            </div>

            <p v-if="form.errors.failed" class="mt-1 text-sm text-center text-warning">{{ form.errors.failed }}</p>

            <Button 
                :disabled="form.processing"
                class="w-full rounded-lg"
                size="lg">
                Login
            </Button>
        </form>
    </div>
</template>